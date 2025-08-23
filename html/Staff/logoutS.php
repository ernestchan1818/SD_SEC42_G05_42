<?php
session_start();

// 清除所有 session
session_unset();

// 销毁 session
session_destroy();

// 跳回 login 页面
header("Location: signinS.php");
exit();
?>
