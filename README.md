# zjmf-discourse-login
针对智简魔方与以Discourse为基础创建的论坛开发的插件，可以通过Discourse论坛账号直接登陆智简魔方。

---
## 修改配置文件
&emsp;&emsp;下载插件文件夹，根据个人需求将`mjjvm.php`文件中的`namespace oauth\mjjvm;`和`class mjjvm`修改为自己的Discourse论坛域名或者是智简魔方的域名，仅需要将文件夹名字与`php`中的定义名相同即可。

---

## 上传文件
&emsp;&emsp;将插件文件夹上传到智简魔方后台的`/public/plugins/oauth`目录下，并准备在Discourse论坛后台进行设置回调。

---
## 配置Discourse论坛
&emsp;&emsp;在Discourse论坛管理员后台的`Community` -> `Login & Authentication` -> `Login`中寻找以下两个设置：
* `Enable Discourse Connect provider`
* `Discourse Connect provider secrets`

打开`Discourse Connect provider secrets`设置，同时设置`domain`为智简魔方的域名，`DiscourseConnect secret`使用命令随机生成10位以上字符，并将其复制到`secret`输入框中，点击`Save`保存设置。

---
## 配置智简魔方
&emsp;&emsp;在智简魔方的插件中，将`DiscourseConnect secret`和`Discourse Domain`设置好，就可以使用了。
