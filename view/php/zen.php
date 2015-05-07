<!DOCTYPE html>
<html>
<head>
  <title><?php if(x($page,'title')) echo $page['title'] ?></title>
  <?php if(x($page,'htmlhead')) echo $page['htmlhead'] ?>
</head>
<body>
   <?php if(x($page,'content')) echo $page['content']; ?>
</body>
</html>
