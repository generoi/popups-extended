<?php if (!empty($options['css']['custom_css'])): ?>
  <style><?php echo $options['css']['custom_css']; ?></style>
<?php endif; ?>
<div
  class="
    spu-box
    reveal
    reveal--<?php echo !empty($options['type']) ? $options['type'] : 'default'; ?>
    <?php echo !empty($options['css']['position']) ? 'reveal--' . $options['css']['position'] : ''; ?>
    <?php echo !empty($options['size']) ? $options['size'] : ''; ?>
    <?php echo !empty($options['theme']) ? $options['theme'] : ''; ?>"
  id="spu-<?php echo $spu_id; ?>"
  data-reveal
  data-overlay="<?php echo $options['overlay'] ? 'true' : 'false'; ?>"
  data-close-on-click="<?php echo $options['close_on_click'] ? 'true' : 'false'; ?>"
  data-close-on-esc="true"
  <?php if (!empty($options['animation'])) : ?>
    data-animation-in="<?php echo $options['animation']; ?>-in" data-animation-out="<?php echo $options['animation']; ?>-out"
  <?php endif; ?>
  <?php if (!empty($options['background_image'])) : ?>
    style="background-image:url(<?php echo $options['background_image']; ?>); background-size: cover;"
  <?php endif; ?>
  data-spu-id="<?php echo $spu_id; ?>"
  data-spu-trigger="<?php echo $options['trigger']; ?>"
  data-spu-trigger-number="<?php echo $options['trigger_number']; ?>"
  data-spu-auto-hide="<?php echo $options['auto_hide']; ?>"
  data-spu-cookie="<?php echo $options['cookie']; ?>"
  data-spu-close-cookie="<?php echo $options['close-cookie']; ?>"
  data-spu-test-mode="<?php echo $options['test_mode']; ?>"
  data-spu-conversion-close="<?php echo $options['conversion_close']; ?>"
  data-event-category="<?php echo $options['event_category']; ?>"
  data-event-label="<?php echo $options['event_label']; ?>"
>
  <div class="reveal__content">
    <?php echo $content; ?>
  </div>

  <button class="close-button" data-close aria-label="<?php _e('Close popup', 'popup-extended'); ?>" type="button">
    <span aria-hidden="true">&times;</span>
  </button>
</div>

