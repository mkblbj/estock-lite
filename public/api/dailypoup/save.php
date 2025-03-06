<?php
header('Content-Type: application/json');
function error($msg)
{
    echo json_encode(array(
        'code' => 500,
        'msg' => $msg
    ));
    exit;
}

function success($msg)
{
    echo json_encode(array(
        'code' => 0,
        'msg' => $msg
    ));
    exit;
}
//保存内容
$content = $_POST['content'];
$is_open = $_POST['is_open'];
$json = array(
    'content' => $content,
    'is_open'=> $is_open,
);
$json = json_encode($json);
if (!file_put_contents('./data.json', $json)) {
    error('保存失败');
}
success('上传成功');
