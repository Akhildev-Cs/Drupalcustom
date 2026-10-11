<?php

namespace Drupal\otp_authentication\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure OTP authentication settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'otp_authentication_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['otp_authentication.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['otp_mail_template'] = [
      '#type' => 'number',
      '#title' => $this->t('OTP mail template'),
      '#default_value' => $this->config('otp_authentication.settings')->get('otp_mail_template'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('otp_mail_template'))) {
      $form_state->setErrorByName('otp_mail_template', $this->t('The value is not correct.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('otp_authentication.settings')
      ->set('otp_mail_template', $form_state->getValue('otp_mail_template'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
