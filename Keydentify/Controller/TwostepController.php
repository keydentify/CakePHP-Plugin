<?php
/**
 * Keydentify CakePHP Plugin - SDK PHP Web
 *
 * Keydentify(tm) : Two Factor Authentication (http://www.keydentify.com)
 * Copyright (c) SAS Keydentify.  (http://www.keydentify.com)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) SAS Keydentify.  (http://www.keydentify.com)
 * @link          http://www.keydentify.com Keydentify(tm) Two Factor Authentication
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CakeEvent', 'Event');
class TwostepController extends KeydentifyAppController {

	function isAuthorized() {
		return true;
	}

	function beforeFilter() {
		parent :: beforeFilter();
		$this->Auth->allow(array ('check_auth'));
		
		# Disable security control
		if (isset($this->Security)) {
			$this->Security->csrfCheck = false;
			$this->Security->validatePost = false;
		}		
	}

	/**
	 * check_auth method
	 *
	 * @return void
	 */
	public function check_auth($redirectToB64 = '') {
				
		App::import('Vendor', 'Keydentify.keydentifyAPI');
		
		$config = Configure::read('Keydentify');
		
		# User have been authenticated by Keydentify ?
		if ($this->request->is('post')) {
						
			$user = null;
			$status = 'failure';
			$error = '';
			
			if (isset($this->data['keydResponse'])) {

				$userModel = ClassRegistry :: init($config['user']['model']);
				$options = array('conditions' => array($config['user']['username_fieldname'] => $this->data['login']));
				$user = $userModel->find('first', $options);

				$check = KeydentifyAPI::checkKeydentifyResponse($config['service_id'], $user[$config['user']['model']][$config['user']['id_fieldname']], $config['secret_key'], $this->data);
				if (!is_bool($check)) {
					$error = __("Keydentify - Two-Factor Authentication failed", "keydentify")." : ".$check;
				} else {
					$status = 'success';
				}
			}
						
			# Send an event to relogin user and made all necessary app logic
			$event = new CakeEvent('Keydentify.check_auth', $this, array(
					'status' => $status, 'user' => $user[$config['user']['model']], 'error' => $error
			));
			$this->getEventManager()->dispatch($event);
			
			# redirect url have been changed ?
			if (isset($event->result['redirect_to'])) {
				$this->data['redirect_to'] = $event->result['redirect_to'];
			} else if (!isset($this->data['redirect_to']) || $this->data['redirect_to'] == '') {
				$this->data['redirect_to'] = $this->Auth->redirect();
			}
			
			$this->redirect($this->data['redirect_to']);
		} else {
			# Check if the user is really authenticated ...
			$user = AuthComponent::user();
			
			if ($user == null) {
				# no account for this credentials, stop here ...
				$this->redirect($this->Auth->redirect());
			} else {
				# Auth Type => 0 : define at user level, 1 : with Keydentify app, 2 : 3D Secure by SMS, 3 : 3D Secure by Phone Call
				$authType = isset($user[$config['user']['auth_type_fieldname']]) ? $user[$config['user']['auth_type_fieldname']] : $config['auth_type'];
				
				$userEmail = isset($user[$config['user']['email_fieldname']]) ? $user[$config['user']['email_fieldname']] : null;
				$userPhoneNumber = isset($user[$config['user']['phone_number_fieldname']]) ? $user[$config['user']['phone_number_fieldname']] : null;
				
				$redirectTo = $this->Auth->redirect();
				if ($redirectToB64 != '') {
					$redirectTo = base64_decode($redirectToB64);
				}
				
				if ($authType > 0) {
					$requestAuth = KeydentifyAPI::requestAuth($config['service_id'], $user[$config['user']['id_fieldname']], $config['secret_key'], $user[$config['user']['username_fieldname']], Configure::read('Config.language'), $redirectTo, $this->request->clientIp(false), $authType, $userEmail, $userPhoneNumber);
					
					if (is_null($requestAuth) || !$requestAuth) {
						$this->redirect($this->Auth->redirect());
					} else if (!is_array($requestAuth)) {
						$this->Session->setFlash($requestAuth, 'flash/error');
						$this->redirect($this->Auth->redirect());
					} else {
						$this->layout = $config['layout'];
						$this->set('html', $requestAuth['html']);
		
						# logout User
						$this->Auth->logout();
					}
				} else {
					# no auth_type specified, stop here ...
					$this->Session->setFlash(__("Keydentify - You have not specified which type of authentication you want to use : by SMS or via Keydentify App", "keydentify"), 'flash/warning');
					$this->redirect($this->Auth->redirect());
				}
			}
		}
	}
}
