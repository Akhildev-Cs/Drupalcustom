<?php

namespace Drupal\otp_authentication\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for OTP authentication routes.
 */
class OtpAuthenticationController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
