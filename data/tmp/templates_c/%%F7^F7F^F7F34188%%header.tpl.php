<?php /* Smarty version 2.6.18, created on 2011-01-03 14:51:13
         compiled from header.tpl */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'escape', 'header.tpl', 6, false),array('function', 'geturl', 'header.tpl', 22, false),array('function', 'breadcrumbs', 'header.tpl', 43, false),)), $this); ?>
<!DOCTYPE html  PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
    <head>
        <title><?php echo ((is_array($_tmp=$this->_tpl_vars['title'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
</title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <link rel="stylesheet" href="/css/styles.css" type="text/css" media="all" />

        <script type="text/javascript" src="/js/prototype.js"></script>
        <script type="text/javascript"
                src="/js/scriptaculous/scriptaculous.js"></script>
    </head>
    <body>
        <div id="header">
            <img src="/images/logo-print.gif" alt="" />
        </div>

        <div id="nav">
            <ul>
                <li<?php if ($this->_tpl_vars['section'] == 'home'): ?> class="active"<?php endif; ?>>
                    <a href="<?php echo smarty_function_geturl(array('controller' => 'index'), $this);?>
">Home</a>
                </li>
                <?php if ($this->_tpl_vars['authenticated']): ?>
                    <li<?php if ($this->_tpl_vars['section'] == 'account'): ?> class="active"<?php endif; ?>>
                        <a href="<?php echo smarty_function_geturl(array('controller' => 'account'), $this);?>
">Your Account</a>
                    </li>
                    <li><a href="<?php echo smarty_function_geturl(array('controller' => 'account','action' => 'logout'), $this);?>
">Logout</a></li>
                <?php else: ?>
                    <li<?php if ($this->_tpl_vars['section'] == 'register'): ?> class="active"<?php endif; ?>>
                        <a href="<?php echo smarty_function_geturl(array('controller' => 'account','action' => 'register'), $this);?>
">Register</a>
                    </li>
                    <li<?php if ($this->_tpl_vars['section'] == 'login'): ?> class="active"<?php endif; ?>>
                        <a href="<?php echo smarty_function_geturl(array('controller' => 'account','action' => 'login'), $this);?>
">Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <div id="content-container" class="column">
            <div id="content">
                <div id="breadcrumbs">
                    <?php echo smarty_function_breadcrumbs(array('trail' => $this->_tpl_vars['breadcrumbs']->getTrail(),'separator' => ' &raquo; '), $this);?>

                </div>

                <h1><?php echo ((is_array($_tmp=$this->_tpl_vars['title'])) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
</h1>