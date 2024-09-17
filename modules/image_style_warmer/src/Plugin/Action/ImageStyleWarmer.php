<?php

namespace Drupal\image_style_warmer\Plugin\Action;

/**
 * An action to warmup image styles.
 *
 * @Action(
 *   id = "image_style_warmer",
 *   label = @Translation("Warmup Image Styles"),
 *   type = "file",
 *   confirm = TRUE,
 * )
 *
 * @deprecated
 *   You need to replace your image_style_warmer action plugins with
 *   image_style_warmer_warmup_file plugin in your system action config
 *   YML files or used in any of your views' config YML files.
 *   There is now also an image_style_warmer_warmup_media plugin which
 *   could be used directly for media entities.
 */
class ImageStyleWarmer extends WarmupFile {

}
