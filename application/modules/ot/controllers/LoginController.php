<?php
/**
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file _LICENSE.txt.
 *
 * This license is also available via the world-wide-web at
 * http://itdapps.ncsu.edu/bsd.txt
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to itappdev@ncsu.edu so we can send you a copy immediately.
 *
 * @package    Login_IndexController
 * @category   Controller
 * @copyright  Copyright (c) 2007 NC State University Office of Information Technology
 * @license    BSD License
 * @version    SVN: $Id: $
 */

/**
 * Allows the user to log in and log out of the application, as well as signup
 * for new accounts and reset passwords.
 *
 * @package    Login_IndexController
 * @category   Controller
 * @copyright  Copyright (c) 2007 NC State University Office of Information Technology
 *
 */
class Ot_LoginController extends Zend_Controller_Action 
{
    /**
     * Action when going to the main login page
     *
     */
    public function indexAction()
    {    	
        $this->_helper->pageTitle('login-index-index:title');
        
		$req = new Zend_Session_Namespace(Zend_Registry::get('siteUrl') . '_request');

        $config = Zend_Registry::get('config');

        $authRealm = new Zend_Session_Namespace('authRealm');
        $authRealm->setExpirationHops(1);

        if (Zend_Auth::getInstance()->hasIdentity()) {
        	$this->_redirect('/');
        }
        
        $adapters = $config->app->authentication->toArray();
        
        $loginForms = array();
        
        foreach ($adapters as $key => $value) {
	        $form = new Zend_Form();
	        $form->setAttrib('id', $value['class'])
	             ->setDecorators(array(
	                 'FormElements',
	                 array('HtmlTag', array('tag' => 'div', 'class' => 'zend_form')),
	                 'Form',
	             ));
	             
            $a = new $value['class'];
            
            if (!$a->autoLogin()) {
		        // Create and configure username element:
		        $username = $form->createElement('text', 'username', array('label' => 'login-index-form:username'));
		        $username->setRequired(true)
		                 ->addFilter('StringTrim');
		        
		        // Create and configure password element:
		        $password = $form->createElement('password', 'password', array('label' => 'login-index-index:password'));
		        $password->addFilter('StringTrim')
		                 ->setRequired(true);
		                 
		        $form->addElements(array($username, $password));
            }
            
			$form->setElementDecorators(array(
                  'ViewHelper',
                  'Errors',      
                  array('HtmlTag', array('tag' => 'div', 'class' => 'elm')), 
                  array('Label', array('tag' => 'span')),      
              ));          
            
	        $loginButton = $form->createElement('submit', 'loginButton', array('label' => 'login-index-index:login'));
	        $loginButton->setDecorators(array(
	                   array('ViewHelper', array('helper' => 'formSubmit'))
	                 ));   

	        $form->addElement($loginButton);
            
            if ($a->allowUserSignUp()) {
                $signupButton = $form->createElement('button', 'signup_' . $key, array('label' => 'login-index-index:signUp'));
		        $signupButton->setDecorators(array(
		                   array('ViewHelper', array('helper' => 'formButton'))
		                ));
		        $signupButton->setAttrib('class', 'signup');
		                
		        $form->addElement($signupButton);
            }
            
			$realm = $form->createElement('hidden', 'realm');
            $realm->setValue($key);
            $realm->setDecorators(array(
                array('ViewHelper', array('helper' => 'formHidden'))
            ));        

            $form->addElement($realm);
            
            $loginForms[$key] = array(
            	'form'        => $form,
            	'realm'       => $key,
            	'name'        => $value['name'],
            	'description' => $value['description'],
            	'autoLogin'   => $a->autoLogin(),
            );
        }
        
        $this->view->loginForms = $loginForms;
                  
        $formUserId   = null;
        $formPassword = null;
        $validForm    = false;
        $messages     = array();
        
        $get = Zend_Registry::get('getFilter');
        
        if (isset($get->realm)) {
            $realm = $get->realm;
        }
                
        if ($this->_request->isPost()) {
        	
        	if (!$form->isValid($_POST)) {
        		$realm = $form->getValue('realm');
        		
        		if (isset($loginForms[$realm]) && $loginForms[$realm]['autoLogin']) {
        			$formUserId = '';
        			$formPassword = '';
        			$validForm = true;
        		}
        		$messages[] = 'msg-error-invalidFormInfo';
        	} else {
        		$validForm = true;
        	}
        }
        
        if ((isset($authRealm->realm) && $authRealm->autoLogin) || ($this->_request->isPost() && $validForm)) {

            if (isset($authRealm->realm) && !$this->_request->isPost()) {
            	$realm = $authRealm->realm;
            } else {
                $realm = $form->getValue('realm');
            }
            
            $username   = ($formUserId) ? $formUserId : $form->getValue('username');
            $password   = ($formPassword) ? $formPassword : $form->getValue('password');
            
            // Set up the authentication adapter
            $authAdapter = new $config->app->authentication->{$realm}->class($username, $password);
            $auth = Zend_Auth::getInstance();            
            
            $authRealm->realm = $realm;
            $authRealm->autoLogin = $authAdapter->autoLogin();
            
            // Attempt authentication, saving the result
            $result = $auth->authenticate($authAdapter);

            $authRealm->unsetAll();
            
            if ($result->isValid()) {

            	$username = $auth->getIdentity()->username;
            	$realm    = $auth->getIdentity()->realm;
            	
            	$account = new Ot_Account();
            	$thisAccount = $account->getAccount($username, $realm);
            	
            	if (is_null($thisAccount)) {
            		$password = $account->generatePassword();
            		
            		$acctData = array(
	            		'username'  => $username,
            			'password'  => md5($password),
            			'realm'     => $realm,
	            		'role'      => (string)$config->user->newAccountRole->val,
            			'lastLogin' => time(),
	            	);
	            		
	            	if ($config->app->loginOptions->generateAccountOnLogin != 1) {
	            		$auth->clearIdentity();
	            		$authAdapter->autoLogout();
	            		throw new Ot_Exception_Access('msg-error-createAccountNotAllowed');
	            	}
	            	
	           		$accountId = $account->insert($acctData);
	            		
	            	$role = $acctData['role'];
	            	
	            	$thisAccount = new stdClass();
	            	$thisAccount->accountId = $accountId;
	            	$thisAccount->username  = $acctData['username'];
	            	$thisAccount->realm     = $realm;
	            	$thisAccount->role      = $role;
	            	
	            } else {
	            	$role = $thisAccount->role;
	            	
	            	$data = array(
	            		'accountId' => $thisAccount->accountId,
	            		'lastLogin' => time(),
	            	);
	            	
	            	$account->update($data, null);
	            }
	            
	            $auth->getStorage()->write($thisAccount);
	            
	            $loggerOptions = array(
	            	'accountId' => $thisAccount->accountId,
	            	'role'      => $thisAccount->role,
	            	'attributeName' => 'accountId',
	            	'attributeId'   => $thisAccount->accountId,
	            );
	            
	            $this->_helper->log(Zend_Log::INFO, 'User Logged In', $loggerOptions);

	            
		            	
		        if (isset($req->uri) && $req->uri != '') {
		        	$uri = $req->uri;
		        	
		        	$req->unsetAll();
		        	
		         	$this->_helper->redirector->gotoUrl($uri);
		        } else {
		            $this->_helper->redirector->gotoUrl('/');
		        }
            } else {
            	if (count($result->getMessages()) == 0) {
                	$messages[] = 'msg-error-invalidUsername';
            	} else {
            		$messages = array_merge($messages, $result->getMessages());
            	}
            }
        }

        // If we have a single adapter that auto logs in, we forward on.
        if (count($loginForms) == 1) {
        	
        	$method = array_pop($loginForms);
        	
            if ($method['autoLogin']) {
            	$authRealm->realm = $key;
                $authRealm->autoLogin = true;
            
                $this->_helper->redirector->gotoUrl('/login/?realm=' . $authRealm->realm);
            }
        }
        
        if (isset($req->uri) && $req->uri != '') {
	        $messages[] = 'msg-info-loginBeforeContinuing';
	    }
        
        $this->view->realm = $realm;
        $this->view->messages = array_merge($this->_helper->flashMessenger->getMessages(), $messages);
        
    }

    /**
     * Action for forgetting a password
     *
     */
    public function forgotAction()
    {
        $config = Zend_Registry::get('config');
        $filter = Zend_Registry::get('getFilter');
        
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $this->_helper->redirector->gotoUrl('/');
            return;
        }            
        
        if (!$filter->realm) {
            throw new Ot_Exception_Input('msg-error-realmNotFound');
        }
        
        $realm = $filter->realm;
        
        $auth = new $config->app->authentication->$realm->class();
        
        if (!$auth->manageLocally()) {
            throw new Ot_Exception_Access('msg-error-authNotSupported');
        }   
        
        
        $form = new Zend_Form();
        $form->setAttrib('id', 'forgotPassword')
	         ->setDecorators(array(
	             'FormElements',
	             array('HtmlTag', array('tag' => 'div', 'class' => 'zend_form')),
	             'Form',
	         ));
        
        $hidden = $form->createElement('hidden', 'realm');
        $hidden->setValue($realm)
               ->clearDecorators()
               ->addDecorators(array(
		           array('ViewHelper'),    // element's view helper
		       ));
        
        // Create and configure username element:
        $username = $form->createElement('text', 'username', array('label' => 'login-index-form:username'));
        $username->setRequired(true)
                 ->addFilter('StringTrim');
        
        $submit = $form->createElement('submit', 'resetPasswordButton', array('label' => 'login-index-forgot:linkReset'));
        $submit->setDecorators(array(
                   array('ViewHelper', array('helper' => 'formSubmit'))
                 ));
        
        $cancel = $form->createElement('button', 'cancel', array('label' => 'form-button-cancel'));
        $cancel->setDecorators(array(
                   array('ViewHelper', array('helper' => 'formButton'))
                ));
                
        $form->addElements(array($username))
        	 ->setElementDecorators(array(
               	  'ViewHelper',
                  'Errors',      
                  array('HtmlTag', array('tag' => 'div', 'class' => 'elm')), 
        	      array('Label', array('tag' => 'span')),      
             )) 
             ->addElements(array($hidden, $submit, $cancel));        

        $messages = array();
        
        if ($this->_request->isPost()) {
            if ($form->isValid($_POST)) {   

	            $account = new Ot_Account();
	            
	            $userAccount = $account->getAccount($form->getValue('username'), $form->getValue('realm'));
	            
	            if (!is_null($userAccount)) {	                
		                 
	            	// Generate key
	            	$text   = $userAccount->username . '@' . $userAccount->realm . '-' . time();
                    $key    = (string)$config->app->loginOptions->passwordReset->cryptKey;
                    $iv     = (string)$config->app->loginOptions->passwordReset->iv;
                    $cipher = constant((string)$config->app->loginOptions->passwordReset->cipher);

                    $code = bin2hex(mcrypt_encrypt($cipher, $key, $text, MCRYPT_MODE_CBC, $iv));
  
			        $this->_helper->flashMessenger->addMessage('msg-info-passwordResetRequest');
				            
			        $loggerOptions = array(
			        	'attributeName' => 'accountId',
			        	'attributeId' => $userAccount->accountId,
			        );
			        
			        $this->_helper->log(Zend_Log::INFO, 'User sent password reset request', $loggerOptions);
			        
			        $et = new Ot_Trigger();
			        $et->setVariables($userAccount->toArray());
			        
			        $et->resetUrl    = Zend_Registry::get('siteUrl') . '/login/index/password-reset/?key=' . $code;
                    $et->loginMethod = $config->app->authentication->$realm->name;
                    
                    $et->dispatch('Login_Index_Forgot');
		                                
		            $this->_helper->redirector->gotoUrl('/login/?realm=' . $realm);
	            } else {
	            	$messages[] = 'msg-error-userAccountNotFound';
	            }
            } else {
            	$messages[] = 'msg-error-invalidFormInfo';
            }
        }

        $this->view->messages = $messages;
        $this->_helper->pageTitle('login-index-forgot:title');             
        $this->view->form = $form;
    }
    
    /**
     * Action for forgetting a password
     *
     */
    public function passwordResetAction()
    {
        $config = Zend_Registry::get('config');
        
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $this->_helper->redirector->gotoUrl('/');
            return;
        }            

        $filter = Zend_Registry::get('getFilter');
        
        if (!$filter->key) {
            throw new Ot_Exception_Input('msg-error-noKeyFound');
        }
        
        $key    = (string)$config->app->loginOptions->passwordReset->cryptKey;
        $iv     = (string)$config->app->loginOptions->passwordReset->iv;
        $cipher = constant((string)$config->app->loginOptions->passwordReset->cipher);        
        $string = pack("H*", $filter->key);
    
        $decryptKey = trim(mcrypt_decrypt($cipher, $key, $string, MCRYPT_MODE_CBC, $iv));
        
        if (!preg_match('/[^@]*@[^-]*-[0-9]*/', $decryptKey)) {
        	throw new Ot_Exception_Input('msg-error-invalidKey');
        }
        
        $userId = preg_replace('/\-.*/', '', $decryptKey);
        $ts = preg_replace('/^[^-]*-/', '', $decryptKey);
        //die($ts);
        $timestamp = new Zend_Date($ts);
        
        $now = new Zend_Date();
        
        $now->subMinute((int)$config->app->loginOptions->passwordReset->numberMinutesKeyIsActive);
        
        if ($timestamp->getTimestamp() < $now->getTimestamp()) {
        	throw new Ot_Exception_Input('msg-error-keyExpired');
        }
        
        $realm = preg_replace('/^[^@]*@/', '', $userId);
        $username = preg_replace('/@.*/', '', $userId);
        
        $auth = new $config->app->authentication->$realm->class();
        
        if (!$auth->manageLocally()) {
            throw new Ot_Exception_Access('msg-error-authNotSupported');
        }   
        
        $account = new Ot_Account();
                
        $thisAccount = $account->getAccount($username, $realm);
                
        if (is_null($thisAccount)) {
        	throw new Ot_Exception_Data('msg-error-userAccountNotFound');
        }
        
        $form = new Zend_Form();
        $form->setAttrib('id', 'resetPassword')
	         ->setDecorators(array(
	             'FormElements',
	             array('HtmlTag', array('tag' => 'div', 'class' => 'zend_form')),
	             'Form',
	         ));
        
        $realmHidden = $form->createElement('hidden', 'realm');
        $realmHidden->setValue($realm)
              ->clearDecorators()
              ->addDecorators(array(
                  array('ViewHelper'),    // element's view helper
              ));
                
        $usernameHidden = $form->createElement('hidden', 'username');
        $usernameHidden->setValue($username)
        	   ->clearDecorators()
        	   ->addDecorators(array(
                   array('ViewHelper'),    // element's view helper
               ));
                            
        $password = $form->createElement('password', 'password', array('label' => 'login-index-passwordReset:new'));
        $password->setRequired(true)
                 ->addValidator('StringLength', false, array(6, 20))
                 ->addFilter('StringTrim')
                 ->addFilter('StripTags');   

        $passwordConf = $form->createElement('password', 'passwordConf', array('label' => 'login-index-passwordReset:confirm'));
        $passwordConf->setRequired(true)
                     ->addValidator('StringLength', false, array(6, 20))
                     ->addFilter('StringTrim')
                     ->addFilter('StripTags');                           

        $submit = $form->createElement('submit', 'resetPasswordButton', array('label' => 'login-index-passwordReset:linkReset'));
        $submit->setDecorators(array(
                   array('ViewHelper', array('helper' => 'formSubmit'))
                 ));
        
        $cancel = $form->createElement('button', 'cancel', array('label' => 'form-button-cancel'));
        $cancel->setAttrib('id', 'cancel');
        $cancel->setDecorators(array(
                   array('ViewHelper', array('helper' => 'formButton'))
                ));
                                     
        $form->addElements(array($password, $passwordConf))
             ->setElementDecorators(array(
               	  'ViewHelper',
                  'Errors',      
                  array('HtmlTag', array('tag' => 'div', 'class' => 'elm')), 
        	      array('Label', array('tag' => 'span')),      
             )) 
             ->addElements(array($realmHidden, $usernameHidden, $submit, $cancel));        
        

        $messages = array();
        
        if ($this->_request->isPost()) {
            if ($form->isValid($_POST)) {        
                if ($form->getValue('password') == $form->getValue('passwordConf')) {
                	
                	$data = array(
                		'accountId' => $thisAccount->accountId,
                		'password'  => md5($form->getValue('password')),
                	);
                	
                	$account->update($data, null);
                	
                    $this->_helper->flashMessenger->addMessage('msg-info-passwordReset');
                    
					$loggerOptions = array(
			        	'attributeName' => 'accountId',
			        	'attributeId' => $data['accountId'],
			        );
			        
			        $this->_helper->log(Zend_Log::INFO, 'User reset their password', $loggerOptions);
                    
                    $this->_helper->redirector->gotoUrl('/login/?realm=' . $realm);
                } else {
                    $messages[] = 'msg-error-passwordsNotMatch';
                }
            } else {
                $messages[] = 'msg-error-invalidFormInfo';
            }
        }

        $this->view->messages = $messages;
        $this->_helper->pageTitle('login-index-passwordReset:title');             
        $this->view->form = $form;
        $this->view->headScript()->appendFile($this->view->baseUrl() . '/ot/scripts/jquery.plugin.passStrength.js');
    }    
    /**
     * Logs a user out
     *
     */
    public function logoutAction()
    {
        $config = Zend_Registry::get('config');
        
        $userId = Zend_Auth::getInstance()->getIdentity();
        foreach ($config->app->authentication as $a) { 
            $auth = new $a->class;
            $auth->autoLogout();  
        }
        
        Zend_Auth::getInstance()->clearIdentity();
                
        $this->_helper->redirector->gotoUrl('/index/index/');  	
    	
    } 
    
    /**
     * allows a user to signup for an account
     *
     */
    public function signupAction()
    {    	
    	$config = Zend_Registry::get('config');
        $get = Zend_Registry::get('getFilter');
        
        if (!$get->realm) {
            throw new Ot_Exception_Input('msg-error-realmNotFound');
        }
        
        if ($config->app->loginOptions->generateAccountOnLogin != 1) {
        	throw new Ot_Exception_Access('msg-error-createAccountNotAllowed');
        }
        
        $account = new Ot_Account();
        
        $realm = $get->realm;
        $this->view->realm = $realm;
        
        $authAdapter = new $config->app->authentication->$realm->class();
        
        if (!$authAdapter->manageLocally()) {
            throw new Ot_Exception_Access('msg-error-authNotSupported');
        }
        
        if (!$authAdapter->allowUserSignUp()) {
            throw new Ot_Exception_Access('msg-error-authoNotAllowed');
        }

        $form = $account->form(array('realm' => $realm), true);
    	
        $messages = array();
    	if ($this->_request->isPost()) {
    	    if ($form->isValid($_POST)) {
    	    	
    	    	if ($form->getValue('password') == $form->getValue('passwordConf')) {
		    		    	    		
                    $accountData = array(
                        'username'     => $form->getValue('username'),
                    	'password'     => md5($form->getValue('password')),
                    	'realm'        => $form->getValue('realm'),
                    	'role'         => $config->user->newAccountRole->val,
                        'emailAddress' => $form->getValue('emailAddress'),
                        'firstName'    => $form->getValue('firstName'),
                        'lastName'     => $form->getValue('lastName')
                    );
                    
                    $thisAccount = $account->getAccount($accountData['username'], $accountData['realm']);
                    
                    if (!is_null($thisAccount)) {
                    	$messages[] = 'msg-error-usernameTaken';
                    } else {
	                    $dba = Zend_Registry::get('dbAdapter');
	                    $dba->beginTransaction();
	                        
	                    try {
	                    	$accountData['accountId'] = $account->insert($accountData);
	                    } catch (Exception $e) {
	                    	$dba->rollback();
	                    	throw $e;
	                    }
			        	
				        if (isset($config->app->accountPlugin)) {
		                    $acctPlugin = new $config->app->accountPlugin;
		                    
		                    $subform = $acctPlugin->addSubForm();
		                    
		                    $data = array('accountId' => $accountData['accountId']);
		                    
		                    foreach ($subform->getElements() as $e) {
		                        $data[$e->getName()] = $form->getValue($e->getName());
		                    }
		                    
		                    try {
		                        $acctPlugin->addProcess($data);
		                    } catch (Exception $e) {
		                        $dba->rollback();
		                        throw $e;
		                    }                   
		                }		

		                $custom = new Ot_Custom();
	                    $attributes = $custom->getAttributesForObject('Ot_Profile');
	        
	                    $data = array();
	                    foreach ($attributes as $a) {
	                        $data[$a['attributeId']] = $form->getValue('custom_' . $a['attributeId']);
	                    }                   
	                    
	                    try {
	                        $custom->saveData('Ot_Profile', $accountData['accountId'], $data);
	                    } catch (Exception $e) {
	                        $dba->rollback();
	                        throw $e;
	                    }              
			        	
			        	$dba->commit();
				        
				        $this->_helper->flashMessenger->addMessage('msg-info-accountCreated');
				        
						$loggerOptions = array(
				        	'attributeName' => 'accountId',
				        	'attributeId' => $accountData['accountId'],
				        );
				        
				        $this->_helper->log(Zend_Log::INFO, 'User Successfully signed up', $loggerOptions);
			            
	                    $et = new Ot_Trigger();
	                    $et->setVariables($accountData);
	                    
	                    $et->password    = $form->getValue('password');
	                    $et->loginMethod = $config->app->authentication->$realm->name;
	                    
	                    $et->dispatch('Login_Index_Signup');		            
			
			            $this->_helper->redirector->gotoUrl('/login/?realm=' . $realm);
			        }
    	    	} else {
    	    		$messages[] = 'msg-error-passwordsNotMatch';
    	    	}
    	    } else {
    	    	$messages[] = 'msg-error-invalidFormInfo';
    	    }
    	}
    	
    	$this->view->messages = $messages;
        $this->view->form = $form;
    	$this->_helper->pageTitle('login-index-signup:title');
    	$this->view->headScript()->appendFile($this->view->baseUrl() . '/ot/scripts/jquery.plugin.passStrength.js');
    	   	
    }
}