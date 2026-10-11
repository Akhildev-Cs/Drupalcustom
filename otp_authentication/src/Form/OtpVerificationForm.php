<?php
namespace Drupal\otp_authentication\Form;
 

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\user\Entity\Role;
use Drupal\Core\Url;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Implements the OTP verification form.
 */
class OtpVerificationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'otp_verification_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $user = \Drupal::currentUser();
    $uid = $user->id(); 
    if ($user->isAnonymous()){
      $response = new RedirectResponse('/user/login');
      $response->send();
    }
    $verification_status = '0';

    $database = \Drupal::database();
    $query = $database->select('otp_authentication', 'u')
    ->fields('u', ['no_of_attempts', 'expire','otp_verified'])
    ->condition('uid', $uid);

    $old_user = $query->execute()->fetchAll();

    $verified_user = $old_user[0]->otp_verified;
    $token_expire_time = $old_user[0]->expire;
    if ($verified_user == '1') {
      $response = new RedirectResponse('/user/' . $current_user_id);
      $response->send();
    }
    \Drupal::messenger()->addMessage($this->t('This website uses two factor authentication. 
        An email with a one time passcode has been emailed to you. 
        Please check your email for the code and enter it below.
        This code is only valid for 15 minutes.'));

    $form['otp'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter OTP'),
      '#prefix' => '<div class="cell-sm-12 otp-auth-user-field">',
      '#suffix' => '</div>',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Verify OTP'),
    ];

    $form['actions']['resend_otp'] = [
      '#type' => 'submit',
      '#value' => $this->t('Resend OTP'),
      '#submit' => ['::reGenerateAndStoreOtp'], // Define custom submit handler for resend button.
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the submitted OTP.
    $submitted_otp = $form_state->getValue('otp');
    $current_user = \Drupal::currentUser();
    $current_user_id = $current_user->id();
    $database = \Drupal::database();
    $select = $database->select('otp_authentication', 'u')
    ->fields('u', ['otp', 'expire'])
    ->condition('uid', $current_user_id, '=')
    ->execute()
    ->fetchAssoc();
    if ($submitted_otp == $select['otp']) {
      \Drupal::messenger()->addMessage($this->t('OTP verification successful.'));
      $database->update('otp_authentication')
      ->expression('otp_verified', 1)
      ->condition('uid', $current_user_id)
      ->execute();
      $roles = $current_user->getRoles();
      $role_labels = [];
      $response = new RedirectResponse('/user/' . $current_user_id);
      $response->send();
    }
    else {
      \Drupal::messenger()->addMessage($this->t('Invalid OTP. Please try again.'), 'error');
    }
  }
  
  public function reGenerateAndStoreOtp(array &$form, FormStateInterface $form_state) {
    $current_user = \Drupal::currentUser();
    $current_user_id = $current_user->id();
    $user_mail = $current_user->getEmail();
    $database = \Drupal::database();
    $query = $database->select('otp_authentication', 'u')
    ->fields('u', ['no_of_attempts', 'expire','otp_verified'])
    ->condition('uid', $current_user_id);
    $old_user = $query->execute()->fetchAll(); 
    if (!empty($old_user)) {
      $no_of_attempts = $old_user[0]->no_of_attempts;
      if($no_of_attempts > 5){
        expierd_entry_delete($current_user_id);
      } else {
        $human_readable_otp = rand(100000, 999999);
        call_otp_mail($user_mail,$human_readable_otp);
        $database->update('otp_authentication')
          ->expression('no_of_attempts', 'no_of_attempts + 1')
          ->expression('expire', strtotime("+15 minutes", time()))
          ->expression('otp', $human_readable_otp)
          ->condition('uid', $current_user_id)
          ->execute();
        \Drupal::messenger()->addMessage($this->t('OTP resent successfully. Please check your inbox.'));
        $url = \Drupal\Core\Url::fromRoute('otp_authentication.otp_verification_form')->toString();
        $response = new RedirectResponse($url);
        $response->send();
      }
    }
  }
}
