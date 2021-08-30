<?php
if (is_file(__DIR__ . "/vendor/autoload.php")) {
    require __DIR__ . "/vendor/autoload.php";
}

// 依赖 jaeger/querylist 包



$ql = new QueryList();

$file = "./apizza导出.html";

$html = file_get_contents($file);

$exist_map = [];

$rt = [];
$c = 0;
$ql->setHtml($html)->find("ul.bs_list_group li a")->each(function (\QL\Dom\Elements $item) use (&$rt,&$c,&$exist_map) {
    $id = $item->attr('href');
    $id = ltrim($id, '#');

    $parent_name = $item->parents('ul.category-lv2')->prev('span')->text();
    $pparent_name = $item->parents('ul.bs_list_group')->prev('span')->text();
    $api_name = $item->text();
    if (empty($pparent_name)) {
        // 附录
        print_r("ppname为空");
        return;
    }

    $pparent_name = $parent_name;
    $parent_name = '';
    if ($pparent_name == $parent_name) {
        $parent_name = '';
    }
    $middle_name = empty($parent_name) ? "-" : "-{$parent_name}-";
    $name = "{$pparent_name}{$middle_name}{$api_name}";

    $api_one = $item->toRoot()->find("#{$id}")->parent();

    $method = $api_one->children('p')->eq(1)->text();
    $method = ltrim($method, "请求方式：");
    $rp = $path = $api_one->children('p')->eq(2)->text();
    // 依情况进行字符串，yapi中path唯一
    $path = str_replace('请求地址：{{hapi}}', '/', $path);
    $path = str_replace('请求地址：', '/', $path);

    if (strpos($rp, '{{v3}}') !== false && isset($exist_map[$path])) {
        print_r("接口{$path}已存在");
        return;
    }
    $exist_map[$path] = $path;

    $req_query = $req_body_form = [];
    $res_body = [
        '$schema' => "http://json-schema.org/draft-04/schema#",
        'type' => 'object',
        'properties' => [],
    ];
//    $res_body = "{\"type\":\"object\",\"title\":\"empty object\",\"properties\":{}}";
    $api_one->children('table.table-hover')->each(function(\QL\Dom\Elements $item)use(&$req_query, &$req_body_form,&$res_body,$name){
        $text = $item->find("thead th")->eq(0)->text();
        $res = [];
        $tbody = $item->find('tbody tr')->each(function(\QL\Dom\Elements $item)use(&$res,$name){
            $res[] = [
                'name' =>$item->children('td')->eq(0)->text(),
                'return_desc' =>$item->children('td')->eq(1)->text(),
                'required' => $item->children('td')->eq(2)->text() == '是' ? 1 : 0,
                'desc' =>$item->children('td')->eq(3)->text(),
                'example' =>$item->children('td')->eq(4)->text(),
                'return_example' =>$item->children('th')->text(),
            ];
        });
        if (empty($res)) {
            print_r("没有找到参数");
            return;
        }
        if($text == 'Query参数名'){
            foreach ($res as $v) {
                if (empty($v['name'])) {
                    continue;
                }
                $req_query[] = [
                    'required' => 1,
                    'name' => $v['name'],
                    'example' => $v['example'],
                    'desc' => $v['desc'],
                ];
            }
        }elseif($text == 'Body参数名'){
            foreach ($res as $v) {
                if (empty($v['name'])) {
                    continue;
                }
                $req_body_form[] = [
                    'required' => 1,
                    'name' => $v['name'],
                    'example' => $v['example'],
                    'desc' => $v['desc'],
                ];
            }
        }elseif($text == '参数名'){
            $properties = [];
            foreach ($res as $v) {
                $properties[$v['name']] = [
                    'type' => 'string',
                    'description' => $v['return_desc'],
                ];
            }
            $res_body['properties'] = $properties;
        }
    });

    if (empty($res_body['properties'])) {
        $res_body['properties'] = new \ArrayObject();
    }
    $res_body = json_encode($res_body);

    $desc = $api_one->children('div.detail-info')->text();


    // 构建分类
    if(!isset($rt[$pparent_name])){
        $rt[$pparent_name] = [
            'name' => $pparent_name,
            'desc' => $pparent_name,
            'list' => [],
        ];
    }

    $c++;
    $api = [
        'query_path' => [
            'path' => $path,
            'params' => [],
        ],
        'status' => 'done',
        "type" => "static",
        "req_body_is_json_schema" => false,
        "res_body_is_json_schema" => true,
        'api_opened' => false,
        "res_body_type" => "json",
        "req_body_type" => "form",
        'method' => $method,
        'title' => $name,
        'index' => 0,
        'path' => $path,
        'req_query' => $req_query,
        'req_body_form' => $req_body_form,
        'desc' => $desc,
        'res_body' => $res_body,
        'tag' => [],
        'req_headers' => [],
        'markdown' => "",
    ];

    $rt[$pparent_name]['list'][] = $api;
});

$rt = array_values($rt);
// yapi导入json，建议使用普通模式导入，并且进行备份
file_put_contents("./yapi导入.json", json_encode($rt));