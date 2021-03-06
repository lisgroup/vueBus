<?php
/**
 * Desc: BusRepository 仓库类
 * User: lisgroup
 * Date: 18-10-03
 * Time: 15:50
 */

namespace App\Http\Repository;


use App\Jobs\SaveBusLine;
use App\Models\BusLine;
use App\Models\Cron;
use App\Models\CronTask;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use QL\QueryList;

class BusRepository
{
    /**
     * @var mixed|QueryList 实例
     */
    protected $ql;
    /**
     * @var string $url 定义采集的url
     */
    protected $url = 'http://www.szjt.gov.cn/BusQuery/APTSLine.aspx?cid=175ecd8d-c39d-4116-83ff-109b946d7cb4';

    /**
     * @var self 单例
     */
    private static $instance;

    // private $cronModel;

    /**
     * @param array $conf
     *
     * @return BusRepository
     */
    public static function getInstent($conf = [])
    {
        if (!isset(self::$instance)) {
            self::$instance = new static($conf);
        }
        return self::$instance;
    }

    /**
     * 2. 获取 Token 的方法，使用 Laravel 的 Cache 缓存，配置在 .env 下 CACHE_DRIVER=file|database|redis
     *
     *
     * @param bool $refresh
     *
     * @return array|bool
     */
    public function getToken($refresh = false)
    {
        /**************     18-01-10 修改获取表单 token 的方法         **************/
        if ($refresh || !$data = Cache::get('token')) {
            // 1.1 获取提交表单的 token  //缓存data post表单提交参数数据
            /**
             * 18年5月15日更新版本
             * $html = httpGet($this->url);
             * $arrayData = $this->ql->html($html)->rules($rules)->query()->getData();
             */
            try {
                $queryList = QueryList::get($this->url, [], [
                    //设置超时时间，单位：秒
                    'timeout' => 5,
                ]);
            } catch (\Exception $e) {
                Log::error('Token 获取失败 error 网络超时: '.$this->url, ['message' => $e->getMessage()]);
                return [];
            }


            // 1.2 自定义采集规则
            $rules = [
                //采集id为__VIEWSTATE这个元素里面的纯文本内容
                '__VIEWSTATE' => array('#__VIEWSTATE', 'value'),
                '__VIEWSTATEGENERATOR' => array('#__VIEWSTATEGENERATOR', 'value'),
                '__EVENTVALIDATION' => array('#__EVENTVALIDATION', 'value'),
                //'ctl00$MainContent$LineName' => array('#MainContent_LineName','value'),         //线路番号
                //'ctl00$MainContent$SearchLine' => array('#MainContent_SearchLine','value'),     //搜索
            ];
            //1.3. 开始采集 -- 作为发送数据的基础
            // $ql = QueryList::getInstance();
            $arrayData = $queryList->rules($rules)->query()->getData();
            // $arrayData = QueryList::Query($html, $rules)->data;
            if (!isset($arrayData[0])) {
                // $this->response()->write('网络异常，请稍后重试');
                return false;
            }
            $data = $arrayData[0];

            // cache('inputData', $data, 86400);
            // Redis::set('token', $data, 86400);
            // 缓存的是分钟数
            Cache::add('token', $data, 180 * 24 * 60);
        }

        return $data;
    }

    /**
     * 3. 发送 POST 请求获取查询的线路列表
     *
     *
     * @param $line
     * @param bool $refresh
     *
     * @return array
     */
    private function getPostBusList($line, $refresh = false)
    {
        /**************************    2. 模拟表单请求获取查询线路列表     ****************************/
        $path = storage_path('framework/bus');
        is_dir($path) || mkdir($path, 0777, true);

        // 2.0 判断是否已经有此条线路搜索
        if ($refresh || !file_exists($path.'/serialize_'.$line.'.txt')) {
            // 1. 获取 Token
            $data = $this->getToken();

            if (!$data) {
                return [];
            }
            // 1.4 构造请求参数
            $input = [
                'ctl00$MainContent$LineName' => $line,        //线路番号
                'ctl00$MainContent$SearchLine' => '搜索',     //搜索
            ];
            //merge 用户输入的线路数据
            $data = array_merge($data, $input);

            //2.2 文件不存在，模拟表单提交 //POST curl 模拟表单提交数据

            /**
             * 18年5月15日更新版本
             * $c_post = http($this->url, $data, true); //线路列表
             * $arrayData = $this->ql->html($c_post)->rules($rules)->query()->getData();
             */
            $queryList = QueryList::post($this->url, $data);

            // 根据线路信息，替换其中的 a 标签
            /*$rules = [
                'content' => array('#MainContent_DATA', 'html')
            ];*/
            $rules = [
                //采集a标签的href属性
                'link' => ['#MainContent_DATA tr td a', 'href'],
                //采集a标签的text文本
                'bus' => ['#MainContent_DATA tr td a', 'text'],
                //采集 tr 下第二个 td 标签的 text文本
                'FromTo' => ['#MainContent_DATA tr td:nth-child(2)', 'text']
            ];
            // $arrayData = QueryList::Query($c_post, $rules)->data;
            $arrayData = $queryList->rules($rules)->query()->getData();
            $str = serialize($arrayData->all());
            //缓存 此条线路替换a标签的数据
            $fileName = $path.'/serialize_'.$line.'.txt';
            file_put_contents($fileName, $str);
            //抛出异常if (!$rs)
            // 车次较多时候数据库操作太频繁，先放入 队列 中批量处理。。。
            /***************** 队列操作 start *******************/
            // $job = (new SaveBusLine($data->toArray()))->delay(Carbon::now()->addMinute(1));
            // dispatch($job);
            SaveBusLine::dispatch($arrayData)->delay(Carbon::now()->addMinute(1));
            /***************** 队列操作 end   *******************/
            // 注释下面代码，使用队列异步处理
            // 车次入库操作
            // foreach (                     $arrayData as $arrayDatum) {
            //     /**
            //      * $arrayDatum 示例如下：
            //      * ['link' => 'APTSLine.aspx?cid=175ec***&LineGuid=21a***&LineInfo=158***','bus' => '158','FromTo' => '园区**',]
            //      */
            //     $this->handleLinkToBusLines($arrayDatum);
            // }
        } else {
            // 2.1 文件存在直接读取
            $serialize = file_get_contents($path.'/serialize_'.$line.'.txt');//线路列表
            $arrayData = unserialize($serialize);
        }

        return $arrayData;
    }

    /**
     * 查询公交如：快线1号 展示列表
     *
     *
     * @param string $line
     * @param bool $refresh 是否强制更新结果
     *
     * @return array
     */
    public function getList($line, $refresh = false)
    {
        if (empty($line)) {
            return [];
        }

        /*** start 逻辑修改： 直接查询是否有此线路的数据 *********/
        $listData = $this->getPostBusList($line, $refresh);

        return $listData;
    }


    /**
     * 获取实时公交站台数据 table 列表
     *
     *
     * @param $path
     * @param $get
     *
     * @return array|bool
     */
    public function getLine($path, $get)
    {
        if (empty($path) || empty($get['cid']) || empty($get['LineGuid']) || empty($get['LineInfo']))
            return false;
        // $paramString = http_build_query($get);
        // $url = 'http://www.szjt.gov.cn/BusQuery/'.$path.'?'.$paramString;
        // 使用自己封装的 Http 请求类，提高代码可控性
        // $httpResult = (new \Curl\Http())->get($url, [], 5);
        // $html = $httpResult['content'] ?? '';
        // $queryList = QueryList::html($html);
        // 实时公交返回的网页数据
        try {
            $url = 'http://www.szjt.gov.cn/BusQuery/'.$path;
            $queryList = QueryList::get($url, $get, [
                // 设置超时时间，单位：秒
                'timeout' => 4,
            ]);
        } catch (\Exception $e) {
            Log::error('busLine 获取失败; error: 网络超时 URL: '.$url, ['message' => $e->getMessage()]);
            return ['to' => '', 'line' => ''];
        }

        /*$rules = [
            'to' => ['#MainContent_LineInfo', 'text'],  //方向
            'content' => ['#MainContent_DATA', 'html']       //具体线路table表格
        ];*/

        $rules = [
            'to' => ['#MainContent_LineInfo', 'text'],  //方向
            // 采集 tr 下的 td 标签的 text 文本
            'stationName' => ['#MainContent_DATA tr td:nth-child(1)', 'text'], // 站台
            'stationCode' => ['#MainContent_DATA tr td:nth-child(2)', 'text'], // 编号
            'carCode' => ['#MainContent_DATA tr td:nth-child(3)', 'text'],  // 车牌
            'ArrivalTime' => ['#MainContent_DATA tr td:nth-child(4)', 'text'], // 进站时间
        ];

        // $arrayData = QueryList::Query($line, $rules)->data;
        $arrayData = $queryList->rules($rules)->query()->getData()->all();
        $to = array_shift($arrayData[0]);
        // $to = $arrayData[0]['to'];
        // unset($arrayData[0]['to']);

        return ['to' => $to, 'line' => $arrayData];
    }

    /**
     * 定时任务：artisan 执行入库操作
     */
    public function cronTaskTable()
    {
        // 无法实现根据数据库设置时间的任务
        $tasks = CronTask::where('is_task', 1)->get();
        foreach ($tasks as $task) {
            // 1. 任务开启前检查时间是否合法
            $time = date('H:i:s');
            if ($task['start_at'] > $time || $task['end_at'] < $time) {
                continue;
            }
            /**********************   line1  start ************************/
            $post = [
                'cid' => $task['cid'],
                'LineGuid' => $task['LineGuid'],
                'LineInfo' => $task['LineInfo'],
            ];
            $data = $this->getLine('APTSLine.aspx', $post)['line'];
            $content = json_encode($data, JSON_UNESCAPED_UNICODE);
            if (!empty($content) && strlen($content) > 20) {
                // 入库操作 1 ----- 木渎
                $cron = ['line_info' => $post['LineInfo'], 'content' => $content];
                $rs = $this->saveCronData($cron);
                if (!$rs) {
                    // 任务失败的记录日志中
                    Log::error('CronTasks 执行失败: 线路名称 '.$post['LineInfo'], $cron);
                } else {
                    Log::info('CronTasks 执行成功: 线路名称 '.$post['LineInfo']);
                }
            } else {
                Log::error('CronTasks 获取 bus 数据失败: 线路名称 '.$post['LineInfo'], $post);
            }
            /**********************   line1  end ************************/
            sleep(2);
        }
    }

    /**
     * 存储或更新数据
     *
     *
     * @param $datas
     *
     * @return bool
     */
    public function updateBusLine($datas)
    {
        $rs = 1;
        foreach ($datas as $data) {
            if (isset($data['link'])) {
                $rs = $this->handleLinkToBusLines($data);
            } else {
                $rs = 0;
            }
        }
        return $rs;
    }

    /**
     * crons 表入库操作
     *
     *
     * @param $crons
     *
     * @return bool
     */
    private function saveCronData($crons)
    {
        // 使用单例会出问题，如插入第一条后面都是更新这条数据。。
        // if (!($this->cronModel instanceof Cron)) {
        //     $this->cronModel = new Cron();
        // }
        // $model = $this->cronModel;
        // foreach ($crons as $key => $cron) {
        //     $model->$key = $cron;
        // }
        // $model = new Cron($crons);
        // return $model->save();
        $day = date('Y-m-d H:i:s');
        $crons += ['created_at' => $day, 'updated_at' => $day];
        return Cron::insert($crons);
    }

    /**
     * bus_lines 表入库操作
     *
     *
     * @param array $lines
     *
     * @return bool
     */
    private function saveBusLines($lines)
    {
        /**
         * 入库前需要先判断是否已经存在
         */
        if (!empty($lines)) {
            $line = BusLine::where('name', $lines['name'])->where('FromTo', $lines['FromTo'])->first();
            if (!empty($line)) {
                $rs = BusLine::where('id', $line['id'])->update($lines);
            } else {
                $model = new BusLine($lines);
                $rs = $model->save();
            }
            return $rs;
        }
        return false;
    }

    /**
     * 处理线路入库操作
     *
     *
     * @param array $arrayDatum 示例如下:
     * $arrayDatum =['link' => 'APTSLine.aspx?cid=17e***&LineGuid=21ae6***&LineInfo=158***','bus' => '158','FromTo' => '园区*',]
     *
     * @return bool
     */
    private function handleLinkToBusLines($arrayDatum)
    {
        // 解析 link 转数组操作
        $arr = parse_url($arrayDatum['link']); // ['path' => 'APTSLine.aspx','query' => 'cid=175ecd8d-c39***']
        parse_str($arr['query'], $lines); // ['cid' => '175ec','LineGuid' => '21aea96','LineInfo' => '15**',]
        $lines['expiration'] = time() + 180 * 24 * 3600;
        $lines['name'] = $arrayDatum['bus'];
        $lines['FromTo'] = $arrayDatum['FromTo'];
        $rs = $this->saveBusLines($lines);
        if (!$rs) {
            // 线路入库失败的记录日志中供查询
            Log::error('BubLines 入库执行失败 error: 线路名称 '.$lines['LineInfo'], $lines);
        } else {
            Log::info('BubLines 入库执行 success: 线路名称 '.$lines['LineInfo']);
        }
        return $rs;
    }

    /**
     * BusRepository constructor.
     *
     *
     * @param $config
     */
    private function __construct($config)
    {
        //$this->ql || $this->ql = QueryList::getInstance();
    }

    /**
     * 不允许 clone
     */
    private function __clone()
    {

    }

    /**
     * @return float
     */
    public function randomTime()
    {
        return (float)sprintf('%.0f', microtime(true) * 1000);
    }
}
