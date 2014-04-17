<?php
include('../../autoload.php');
include('../../vendor/autoload.php');

$destination = realpath('../fixitures/container');

$pictureHandler = new Sirius\Upload\Handler($destination);
$pictureHandler->addRule(Sirius\Upload\Handler::RULE_IMAGE, array('allowed' => array('jpg', 'png')));

$resumeHandler = new Sirius\Upload\Handler($destination);
$resumeHandler->addRule(Sirius\Upload\Handler::RULE_EXTENSION, array('allowed' => array('doc', 'docx', 'pdf')));

$upload = new Sirius\Upload\HandlerAggregate();
$upload->addHandler('picture', $pictureHandler);
$upload->addHandler('resume', $resumeHandler);

$result = false;
if ($_FILES) {
    $result = $upload->process($_FILES);
}

?>
<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title>Sirius\Upload test</title>
</head>
<body>

<?php if ($result) { ?>

    <?php foreach ($result as $key => $file) { ?>
        <h3><?php echo $key ?> upload results:</h3>
        <?php if ($file->isValid()) { ?>
            SUCCESS! Uploaded as <?php echo $file->name ?>
        <?php } else { ?>
            ERROR! Error messages:<br/>
            <?php echo implode('<br>', $file->getMessages());?>
        <?php } ?>
    <?php } ?>

<?php } else { ?>

    <form method="post" enctype="multipart/form-data">
    <div>Profile picture: <input type="file" name="picture"></div>
    <div>Resume: <input type="file" name="resume"></div>
    <input type="submit" value="Send">
</form>

<?php } ?>

</body>
</html>