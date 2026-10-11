<?php

namespace Drupal\otp_authentication\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\SessionManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * OTP authentication event subscriber.
 */
class OtpAuthenticationSubscriber implements EventSubscriberInterface {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The database object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The session manager.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   The session manager.
   */

  public function __construct(MessengerInterface $messenger, Connection $connection, SessionManagerInterface $session_manager) {
    $this->messenger = $messenger;
    $this->database = $connection;
    $this->sessionManager = $session_manager;
  }

  /**
   * Kernel request event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Response event.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    $this->messenger->addStatus(__FUNCTION__);
  }

  /**
   * Kernel response event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   Response event.
   */
  public function onKernelResponse(FilterResponseEvent $event) {
    $this->messenger->addStatus(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    return [
      KernelEvents::REQUEST => ['onKernelRequestpages'],      
    ];
  }
  public function onKernelRequestpages(){
    // get current user
    $user = \Drupal::currentUser();
    $uid = $user->id(); 

    $current_path = \Drupal::service('path.current')->getPath();
    $urlList = array(
      
      "/user/logout",
      "/user/login",
    );

    // Bypass for Super admin and Anonymous users
    if ($uid != 1 && !$user->isAnonymous()) {
      //check if user is calling the urls in the url list
      if (!in_array($current_path, $urlList)) {
        // Verify if OTP is verfied
        $is_verified = $this->getVerificationSatus($uid);
        switch ($is_verified) {
          case '0':
            # Need to show OTP page
            if ($current_path != '/auth/otp-verification') {
              $url = \Drupal\Core\Url::fromRoute('otp_authentication.otp_verification_form')->toString();
              // Create a RedirectResponse object with the login page URL.
              $response = new RedirectResponse($url);
              $response->send();
            }
            break;
  
          case '1':
            # code...
            // Do nohing
            break;
  
          case '2' :
            # logout and regrect to login
            user_logout();
            $database = \Drupal::database();
            $database->delete('otp_authentication')
              ->condition('uid', $uid)
              ->execute();
            $url = \Drupal\Core\Url::fromRoute('user.login')->toString();
            // Create a RedirectResponse object with the login page URL.
            $response = new RedirectResponse($url);
            $response->send();
            break;
        }

      }
    }
  }

  /**
   * Gets user verification status
   * 
   * Verifivation not completed 0
   * Verifivation completed 1
   * needs relogin 2
   * 
   * 
   */
  public function getVerificationSatus($user_id) {
    $verification_status = '0';
    $database = \Drupal::database();
    $query = $database->select('otp_authentication', 'u')
    ->fields('u', ['no_of_attempts', 'expire','otp_verified'])
    ->condition('uid', $user_id);
    $old_user = $query->execute()->fetchAll();
    $verified_user = $old_user[0]->otp_verified;
    $token_expire_time = $old_user[0]->expire;
    if ($verified_user == 1) {
      $verification_status =  '1';
    }
    else {
      if ($token_expire_time) {
        if ($token_expire_time < time()) {
          $verification_status = '2';
        }
      }
    }
    return $verification_status;
  }
}


