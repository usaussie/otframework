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
 * @package    Ot_Account
 * @category   Model
 * @copyright  Copyright (c) 2007 NC State University Office of
 *             Information Technology
 * @license    BSD License
 * @version    SVN: $Id: $
 */

/**
 * Model to interact with user profiles
 *
 * @package    Ot_Account
 * @category   Model
 * @copyright  Copyright (c) 2007 NC State University Office of
 *             Information Technology
 *
 */
class Ot_Model_DbTable_Account extends Ot_Db_Table
{
    /**
     * Name of the table in the database
     *
     * @var string
     */
    protected $_name = 'tbl_ot_account';

    /**
     * Primary key of the table
     *
     * @var string
     */
    protected $_primary = 'accountId';

    /**
     * The minimum length for a password
     */
    protected $_minPasswordLength = 5;

    /**
     * The maximum length for a generated password
     */
    protected $_maxPasswordLength = 20;
    
    
    /**
     * Get's an account info, with additional information, by an account ID
     * 
     * @param type $accountId
     * @return stdClass
     */
    public function getByAccountId($accountId)
    {
        $thisAccount = $this->find($accountId);
        
        if (is_null($thisAccount)) {
            return null;
        }
                
        return $this->_mergeAccountData($thisAccount);
        
    }
    
    /**
     * Get's an account info, with additional info, by a username and realm
     * 
     * @param type $username
     * @param type $realm
     * @return stdClass
     */
    public function getByUsername($username, $realm)
    {
        $where = $this->getAdapter()->quoteInto('username = ?', $username)
               . ' AND '
               . $this->getAdapter()->quoteInto('realm = ?', $realm);

        $result = $this->fetchAll($where);

        if ($result->count() != 1) {
            return null;
        }
        
        return $this->_mergeAccountData($result->current());
    }
    
    protected function _mergeAccountData(Zend_Db_Table_Row $data)
    {                
        $data = (object) $data->toArray();      
        
        $rolesModel = new Ot_Model_DbTable_AccountRoles();

        $where = $rolesModel->getAdapter()->quoteInto('accountId = ?', $data->accountId);

        $roles = $rolesModel->fetchAll($where);

        $roleList = array();
        foreach ($roles as $r) {
            $roleList[] = $r['roleId'];
        }
        
        $data->role = $roleList;
        
        $aar = new Ot_Account_Attribute_Register();
        
        $vars = $aar->getVars($data->accountId);
        
        $data->attributes = array();
                
        foreach ($vars as $varName => $var) {
            $data->attributes[$varName] = $var->getValue();
        }

        $authAdapter = new Ot_Model_DbTable_AuthAdapter();
        $adapter = $authAdapter->find($data->realm);
                
        $data->authAdapter = array(
            'obj'         => new $adapter->class(),
            'enabled'     => $adapter->enabled,
            'name'        => $adapter->name,
            'description' => $adapter->description
        );
                
        return $data;
    }
    
    /**
     * Checks to see if an account exists for the given realm
     * 
     * @param type $username
     * @param type $realm
     * @return bool
     */
    public function accountExists($username, $realm)
    {
        $where = $this->getAdapter()->quoteInto('username = ?', $username)
               . ' AND '
               . $this->getAdapter()->quoteInto('realm = ?', $realm);

        $result = $this->fetchRow($where);
        

        return (!is_null($result));
    }
    
    
    /**
     * inserts a new user and also takes care of account_roles if $data['role'] is set
     */
    public function insert(array $data)
    {
        $inTransaction = false; //whether or not we're in a transaction prior to this
        $dba = $this->getAdapter();
        
        try {
            $dba->beginTransaction();
        } catch (Exception $e) {
            $inTransaction = true;
        }

        $roleIds = array();
        if (isset($data['role']) && count($data['role']) > 0) {
            $roleIds = (array)$data['role'];
            unset($data['role']);
        }
        
        try {
            $accountId = parent::insert($data);
        } catch(Exception $e) {

            if (!$inTransaction) {
                $dba->rollback();
            }

            throw new Ot_Exception('Account insert failed.');
        }

        if (count($roleIds) > 0) {
            $accountRoles = new Ot_Model_DbTable_AccountRoles();

            foreach($roleIds as $r) {
                $accountRoles->insert(array(
                    'accountId' => $accountId,
                    'roleId'    => $r,
                ));
            }
        }
            
        if (!$inTransaction) {
            $dba->commit();
        }
            
        return $accountId;
    }

    /**
     * updates the row
     * if you supply an array of role ids, it will update them correctly in the account_roles table
     */
    public function update(array $data, $where)
    {
        $rolesToAdd = array();
        
        if (isset($data['role']) && count($data['role']) > 0) {
            $rolesToAdd = (array)$data['role'];
            unset($data['role']);
        }
        
        $updateCount = parent::update($data, $where);
        if (count($rolesToAdd) < 1) {
            return $updateCount;
        }
        
        $accountRoles = new Ot_Model_DbTable_AccountRoles();
        $accountRolesDba = $accountRoles->getAdapter();

        $accountId = $data['accountId'];

        if (isset($rolesToAdd) && count($rolesToAdd) > 0 && $accountId) {
            try {
                $where = $accountRolesDba->quoteInto('accountId = ?', $accountId);
                $accountRoles->delete($where);
                
                foreach($rolesToAdd as $roleId) {
                    $d = array(
                        'accountId' => $accountId,
                        'roleId' => $roleId,
                    );
                    $accountRoles->insert($d);
                }
                
            } catch(Exception $e) {
                throw $e;
            }
        }
        return $updateCount;
    }

    public function delete($where)
    {
        $inTransaction = false; //whether or not we're in a transaction prior to this
        $dba = $this->getAdapter();
        
        try {
            $dba->beginTransaction();
        } catch (Exception $e) {
            $inTransaction = true;
        }
        
        $thisAccount = $this->fetchRow($where);
                
        $accountRoles = new Ot_Model_DbTable_AccountRoles();
        $apiApps = new Ot_Model_DbTable_ApiApp();
        $custom = new Ot_Model_Custom();
        $aar = new Ot_Account_Attribute_Register();
        
        try {
            $deleteResult = parent::delete($where);
            
            $accountRoles->delete($where);
            
            $apiApps->delete($where);
            
            $custom->deleteData('Ot_Profile', $thisAccount->accountId);
            
            $aar->delete($thisAccount->accountId);
            
        } catch (Exception $e) {                
            if (!$inTransaction) {
                $dba->rollback();
            }
            
            throw new Ot_Exception('Account delete failed.');
        }
            
        if (!$inTransaction) {
            $dba->commit();
        }
        
        return $deleteResult;
    }

    public function generatePassword()
    {
        return substr(md5(microtime()), 2, 2 + $this->_minPasswordLength);
    }

    public function generateApiCode()
    {
        return md5(microtime() * 34);
    }

    public function verify($accessCode)
    {
        $where = $this->getAdapter()->quoteInto('apiCode = ?', $accessCode);
        
        $result = $this->fetchAll($where, null, 1);

        if (count($result) != 1) {
            throw new Exception('Code not found');
        }

        return $result[0];
    }

    public function getAccountsForRole($roleId, $order = null, $count = null, $offset = null)
    {
        $rolesDb = new Ot_Model_DbTable_AccountRoles();

        $where = $rolesDb->getAdapter()->quoteInto('roleId = ?', $roleId);

        $roles = $rolesDb->fetchAll($where)->toArray();
        
        $accountIds = array();
        foreach ($roles as $role) {
            $accountIds[] = $role['accountId'];
        }

        if (count($accountIds) > 0) {
            $where = $this->getAdapter()->quoteInto('accountId IN (?)', $accountIds);
            
            return $this->fetchAll($where, $order, $count, $offset);
        } 
        
        return null;        
    }
    
    /**
     * 
     * @param string $unityId
     * @param array $newRoleId - integer or array of integers for the new role Ids
     */
    public function changeAccountRoleForUnityId($unityId, $newRoleId)
    {
        $thisAccount = $this->getByUsername($unityId, 'wrap');
        $accountRoles = new Ot_Model_DbTable_AccountRoles();
 
        if (!is_null($thisAccount)) {
            $data = array(
                'accountId' => $thisAccount->accountId,
                'role'      => $newRoleId
            );
            
            $newRoleId = (array)$newRoleId;
            
            $dba = $this->getAdapter();
            $dba->beginTransaction();
            
            try {
                $this->update($data, null);
                
                $dba->commit();
                return;
            } catch (Exception $e) {
            $dba->rollBack();
                throw $e;
            }
        } else {
            throw new Exception('Account not found');
        }
        
    }
    
    public function createNewUserForUnityId($unityId, $roleId)
    {    
        $thisAccount = $this->getByUsername($unityId, 'wrap');
        $accountRoles = new Ot_Model_DbTable_AccountRoles();
        
        if (is_null($thisAccount)) {
            
            $data = array (
                'username'  => $unityId, 
                'realm'     => 'wrap',
                'password'  => md5($this->generatePassword()),
                'timezone'  => 'America/New_York',
                'role'      => $roleId,
            );

            $dba = $this->getAdapter();
            $dba->beginTransaction();
            
            $accountRolesDba = $accountRoles->getAdapter();
            
            try {
                $newAccountId = $this->insert($data);
                
                $dba->commit();
                return $newAccountId;
                
            } catch (Exception $e) {
            $dba->rollBack();
                throw $e;
            }
        } else {
            throw new Exception('Account already exists');
        }
    }
}