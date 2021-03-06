<?php
/**
 * Desc: 具体实现方法在仓库 BusRepository.php 文件中
 * User: lisgroup
 * Date: 18-10-03
 * Time: 11:52
 */

namespace App\Http\Controllers\Bus;

use App\Http\Repository\BusRepository;
use Illuminate\Http\Request;

class IndexController extends CommonController
{
    /**
     * 首页展示页面
     *
     * @return mixed
     */
    public function index()
    {
        // BusRepository::getInstent()->cronTaskTable();
        if (PHP_SAPI == 'cli') {
            // $result = BusRepository::getInstent()->busTask();
            return ['code' => 0, 'msg' => 'success', 'result' => ['data' => 'task '.date('Y-m-d H:i:s')]];
        } else {
            return ['code' => 0, 'msg' => 'success'];
        }
    }

    /**
     * 采集 bus 网址是表单数据
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function getList(Request $request)
    {
        $line = $request['linename'];
        $line = preg_replace('/快\b(\d)/', '快线$1号', $line);
        $list = BusRepository::getInstent()->getList($line);

        // return $this->exportData($list);
        return $this->out(200, $list);
    }

    /**
     * 获取实时公交数据table列表 --- 根据data-href 地址，请求 szjt.gov.cn 查询实时公交数据，不能缓存
     * 获取实时公交数据table列表，数据来自 szjt.gov.cn，需要限制访问频率，1秒一次请求
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function busLine(Request $request)
    {
        $data = [];
        // 根据 POST 数据，获取路线信息
        // $request->input('href', '');
        // $post = input('post.', '', 'htmlspecialchars');
        $post = $request->all();
        // $postJson = '{"href":"APTSLine.aspx?cid=175ecd8d-c39d-4116-83ff-109b946d7cb4","LineGuid":"af9b209b-f99d-4184-af7d-e6ac105d8e7f","LineInfo":"\u5feb\u7ebf1\u53f7(\u6728\u6e0e\u516c\u4ea4\u6362\u4e58\u67a2\u7ebd\u7ad9)"}';
        // $post = json_decode($postJson, true);
        // 'href' => string 'APTSLine.aspx?cid=175ecd8d-c39d-4116-83ff-109b946d7cb4' (length=54)  'LineGuid' => string '9d090af5-c5c6-4db8-b34e-2e8af4f63216' (length=36)  'LineInfo' => string '1(公交一路新村)' (length=21)
        if (!empty($post) && !empty($post['href']) && !empty($post['LineGuid']) && !empty($post['LineInfo'])) {
            $parseUrl = parse_url($post['href']);
            parse_str($parseUrl['query'], $params);
            unset($post['href']);
            $post['cid'] = $params['cid'];
            $data = BusRepository::getInstent()->getLine($parseUrl['path'], $post);
        }

        return $this->out(200, $data);
    }

    /**
     * 查询公交线路--全文索引
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $params = $request->all(['wd']);
        if (empty($params['wd'])) {
            return $this->out(200, [], 'param error');
        }
        $list = \App\Models\Line::search($params['wd'])->get()->toArray();
        return $this->out(200, $list);
    }

}
