<fieldset id="openlayerszoom-item-metadata">
    <h2><?php echo __(' OpenLayersZoom'); ?></h2>
    <div class="field">
        <p class="explanation">
            <?php echo __('Zoomify all images of selected items in order to display them via OpenLayersZoom.');
                echo ' ' . __('Warning: Zoomify process is heavy, so you may need to increase memory and time on your server before proceeding.');
            ?>
        </p>
        <div class="two columns alpha">
            <?php echo $this->formLabel('openlayerszoom_zoomify', __('Zoomify')); ?>
        </div>
        <div class="inputs five columns omega">
            <?php
                echo $this->formCheckbox('custom[openlayerszoom][zoomify]', null, array(
                    'checked' => false, 'class' => 'zoomify-checkbox'));
            ?>
        </div>
    </div>
</fieldset>
