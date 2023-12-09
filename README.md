# pdd-merchant
# 拼多多商家组件
> 本组件包适用于php开发 基于拼多多商家后台进行全爬虫逆向提取协议而封装

## composer安装命令
```shell
compose yunzhiyike/pdd-merchant
```

## 使用说明
```php
$pddMerchant = new PddMerchant(60, '远程拼多多加密服务地址', '远程拼多多加密服务token');
 // 发送验证码
$pddMerchant->sendSmsCode('拼多多商家账号/子账号');
// 登录 返回登录账号信息和cookie
$info = $pddMerchant->login('拼多多商家账号/子账号', '密码', '验证码');
// 获取登录成功后的授权cookie
$cookie = $pddMerchant->getCookie();
```

# 免责声明
> 禁止使用该组件包进行任何违法犯罪行为，否则后果自负与作者无关！
