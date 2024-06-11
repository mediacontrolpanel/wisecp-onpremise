    <div class="clear"></div>
    <br>
    <div id="order-service-detail-btns">
        <div id="panel_buttons">

            <form action="https://<?php echo $server['name'] . ':' . $server['port']; ?>" method="post" target="_blank">
                <input type="hidden" name="username" value="<?php echo $user['email']; ?>" />
                <input type="hidden" name="user_password" value="<?php echo $config['password']; ?>" />
                <input type="submit" class="btn btn-primary" value="Login to Control Panel" />
            </form>'

        </div>
    </div>
    <div class="formcon">
        <div class="yuzde30">Panel URL</div>
        <div class="yuzde70">
            <a href="https://<?php echo $server['name'] . ':' . $server['port']; ?>">https://<?php echo $server['name'] . ':' . $server['port']; ?></a>
        </div>
    </div>
    <div class="formcon">
        <div class="yuzde30"><?php echo __("website/account_products/server-login-username"); ?></div>
        <div class="yuzde70">
            <?php echo $user["email"]; ?>
        </div>
    </div>

    <div class="formcon">
        <div class="yuzde30"><?php echo __("website/account_products/server-login-password"); ?></div>
        <div class="yuzde70">
            <?php echo $config["password"]; ?>
        </div>
    </div>
<?php if(in_array($data['plugin'],['shoutcast198','shoutcast2','icecast','icecast_kh'])): ?>
<hr />
    <div class="formcon">
        <div class="yuzde30">Radio IP / Hostname</div>
        <div class="yuzde70"><?php echo $server['name'] ?></div>
    </div>

    <div class="formcon">
        <div class="yuzde30">Radio Port</div>
        <div class="yuzde70"><?php echo $portbase; ?></div>
    </div>
<?php endif; ?>

<?php
if(isset($package) && $package)
{
    array_pop($package["features"]);
    array_pop($package["features"]);

    foreach($package["features"] AS $k=>$v)
    {
        ?>
        <div class="formcon">
            <div class="yuzde30"><?php echo $k; ?></div>
            <div class="yuzde70"><?php echo $v; ?></div>
        </div>
        <?php
    }
}
?>