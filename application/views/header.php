<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AuToDo</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <link href="<?php echo base_url(); ?>assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo base_url(); ?>assets/css/bootstrap-responsive.css" rel="stylesheet">
    <link href="<?php echo base_url(); ?>assets/css/custom-theme/jquery-ui-1.8.16.custom.css" rel="stylesheet">
    <link href="<?php echo base_url(); ?>assets/css/style.css" rel="stylesheet">

</head>
<body>

<!-- Navigation header -->
<div class="navbar navbar-fixed-top">
    <div class="navbar-inner">
        <div class="container">

            <!-- Main nav bar elements -->
            <div id="nav-navbar-autodo">

                <a class="brand" href="<?php echo base_url();?>index.php">AuToDo</a>

                <ul class="nav pull-right">

                    <?php if (isset($user)): ?>
                        <li class="login_info"><?php echo $user->name; ?></li>
                        <li><a data-toggle="modal" href="#add-task" class="btn btn-success add-task"><i class="icon-white icon-plus"></i></a></li>
                    <?php endif ?>
                    <li><a href="<?php echo base_url();?>index.php/authenticate" class="btn btn-info">
                        <?php if (isset($user)){
                            echo "Logout";
                        }else{
                            echo "Log In";
                        } ?>
                    </a></li>
                </ul>

            </div>

        </div>
    </div>
</div>