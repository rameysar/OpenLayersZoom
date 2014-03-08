<fieldset id="fieldset-openlayerszoom"><legend><?php echo __('OpenLayersZoom'); ?></legend>
<div class="field">
    <div class="two columns alpha">
        <label for="openlayerszoom_tiles_dir">
            <?php echo __('Directory path of tiles files'); ?>
        </label>
    </div>
    <div class='inputs five columns omega'>
        <?php echo get_view()->formText('openlayerszoom_tiles_dir', get_option('openlayerszoom_tiles_dir'), array('size' => 50)); ?>
        <p class="explanation">
            <?php echo __('Directory path where tiles files are stored.');
            echo ' ' . __('Default directory is "%s".', get_option('openlayerszoom_tiles_dir')); ?>
        </p>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <label for="openlayerszoom_tiles_web">
            <?php echo __('Base Url of tiles files'); ?>
        </label>
    </div>
    <div class='inputs five columns omega'>
        <?php echo get_view()->formText('openlayerszoom_tiles_web', get_option('openlayerszoom_tiles_web'), array('size' => 50)); ?>
        <p class="explanation">
            <?php echo __('Equivalent web url.'); ?>
        </p>
    </div>
</div>
<div class="field">
    <div class="two columns alpha">
        <label for="openlayerszoom_use_public_head">
            <?php echo __('Automatically add css and javascript'); ?>
        </label>
    </div>
    <div class='inputs five columns omega'>
        <?php echo get_view()->formCheckbox('openlayerszoom_use_public_head', TRUE, array('checked' => (boolean) get_option('openlayerszoom_use_public_head'))); ?>
        <p class="explanation">
            <?php echo __('OpenLayersZoom needs css and javascript to run. It is added automatically and only when needed.'); ?>
            <?php echo ' ' . __('Unckeck if you prefer to load them yourself in case of complex items or javascript.'); ?>
        </p>
    </div>
</div>
</fieldset>