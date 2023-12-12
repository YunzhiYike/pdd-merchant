# pdd-merchant
# 拼多多商家组件
> 本组件包适用于php开发 基于拼多多商家后台进行全爬虫逆向提取协议而封装

## composer安装命令
```shell
compose yunzhiyike/pdd-merchant
```

## 使用说明
### 1、登录流程
```php
////////////////  登录步骤 /////////////////
$pddMerchant = new PddMerchant(60, '远程拼多多加密服务地址', '远程拼多多加密服务token');
 // 发送验证码
$pddMerchant->sendSmsCode('拼多多商家账号/子账号');
// 登录 返回登录账号信息和cookie
$info = $pddMerchant->login('拼多多商家账号/子账号', '密码', '验证码');
// 获取登录成功后的授权cookie
$cookie = $pddMerchant->getCookie();


////////////////  订单导出步骤 /////////////////
// 创建订单导出任务
$startTime = 1698768000;
$endTime = 1702362388;
$pddMerchant->createOrderTask($startTime, $endTime);
// 获取订单导出任务列表
$res = $pddMerchant->getOrderTaskList(1, 10);

$orderTaskList = $res['pageItems'];
foreach ($orderTaskList as $orderTask) {
       // 根据刚刚导出的时间范围条件找到任务id
       if ($orderTask['groupStartTime'] == $startTime && $orderTask['groupEndTime'] == $endTime) {
       $taskId = $orderTask['id'];
       // 根据找到的任务id获取真实的下载地址
       $downloadUrl = $pddMerchant->getOrderTaskDownloadUrl($taskId);
       var_dump($downloadUrl);
       break;
   }
}

////////////////  评价查询步骤 /////////////////
```
### 2、财务接口
> 登录后还无法直接调用财务接口来获取财务数据还需要对登录cookie进行财务二次鉴权
```php
$pddMerchant = new PddMerchant(60, '远程拼多多加密服务地址', '远程拼多多加密服务token');
 // 发送验证码
$pddMerchant->sendSmsCode('拼多多商家账号/子账号');
// 登录 返回登录账号信息和cookie
$info = $pddMerchant->login('拼多多商家账号/子账号', '密码', '验证码');
// 财务二次鉴权
$pddMerchant->finances2Auth();
// 调用获取财务月度数据报表
$billingReport = $pddMerchant->queryMallBalanceMonthlySummary('2023-01', '2024-01');
// 新建财务流水导出任务
$pddMerchant->createBillDownloadTask(1698768000, time());
// 获取财务流水任务列表（在这里可以下载财务流水）
$taskList = $pddMerchant->queryBillDownloadTaskList(1, 10);
```

# 免责声明
> 禁止使用该组件包进行任何违法犯罪行为，否则后果自负与作者无关！
