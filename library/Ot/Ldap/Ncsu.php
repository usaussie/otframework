<?php
/**
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * This license is also available via the world-wide-web at
 * http://itdapps.ncsu.edu/bsd.txt
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to itappdev@ncsu.edu so we can send you a copy immediately.
 *
 * @package    Ot_Ldap_Ncsu
 * @category   Library
 * @copyright  Copyright (c) 2007 NC State University Office of      
 *             Information Technology
 * @license    http://itdapps.ncsu.edu/bsd.txt  BSD License
 * @version    SVN: $Id: $
 */

/**
 * This LDAP module allows easy interaction between PHP and NCSU's LDAP server.  The
 * module extends the main LDAP driver which allows for persistant connections.
 *
 * @package    Ot_Ldap_Ncsu
 * @category   Library
 * @copyright  Copyright (c) 2007 NC State University Office of      
 *             Information Technology
 */
class Ot_Ldap_Ncsu extends Ot_Ldap_Driver
{

    /**
     * Default context for most searches
     *
     * @var string
     */
    protected $_defaultContext = "ou=people,dc=ncsu,dc=edu";


    /**
     * Constructor for LDAP NCSU
     *
     */
    public function __construct()
    {
    }

    /**
     * Convenience function to search on first and last name
     *
     * @param string $firstName - The first name (or part of the first name)
     * @param string $lastName - The last name (or part of the last name)
     * @param bool $allFields - Boolean to return all or just selected fields
     * @return LDAP result set
     * @throws Ot_Resource_Exception on LDAP Error from Ot_Ldap_Driver::search()
     */
    public function searchOnFirstAndLastName($firstName, $lastName, $allFields = false)
    {

        // Setup return fields
        if ($allFields) {
            $this->setReturnFieldsToDefault();
        } else {
            $this->addReturnField("uid");
            $this->addReturnField("cn");
            $this->addReturnField("givenName");
            $this->addReturnField("ncsuMiddleName");
            $this->addReturnField("sn");
            $this->addReturnField("telephoneNumber");
            $this->addReturnField("ncsuCampusID");
        }

        // Do search based on data passed to the function
        if ($firstName == "" || $lastName == "") {
            if ($firstName == "") {
                return $this->search("(sn=" . $lastName . "*)", $this->_defaultContext);
            }

            return $this->search("(givenName=" . $firstName . "*)", $this->_defaultContext);
        }

        return $this->search("(&(givenName=" . $firstName . "*)(sn=" . $lastName . "*))", $this->_defaultContext);

    }


    /**
     * Convenience function to search on phone numbers
     *
     * @param string $phone - The phone number to search for
     * @param bool $allFields - Boolean to return all or just selected fields
     * @throws Ot_Resource_Exception on LDAP Error from Ot_Ldap_Driver::search()
     * @return LDAP Result Set
     */
    public function searchOnPhone($phone, $allFields = false)
    {

        // Setup return fields
        if ($allFields) {
            $this->setReturnFieldsToDefault();
        } else {
            $this->addReturnField("uid");
            $this->addReturnField("cn");
            $this->addReturnField("givenName");
            $this->addReturnField("ncsuMiddleName");
            $this->addReturnField("sn");
            $this->addReturnField("telephoneNumber");
            $this->addReturnField("ncsuCampusID");
        }

        return $this->search("telephoneNumber=" . $phone, $this->_defaultContext);
    }


    /**
     * Convenience function to return all users in a group
     *
     * @param string $group - Name of the group to search on
     * @param bool $allFields - Boolean to return all or just selected fields
     * @return LDAP Result set
     * @throws Ot_Resource_Exception on LDAP Error from Ot_Ldap_Driver::search()
     */
    public function getUsersInGroup($group, $allFields = false)
    {

        // Setup return fields
        if ($allFields) {
            $this->setReturnFieldsToDefault();
        } else {
            $this->addReturnField("memberUid");
        }

        // Do the search
        $result = $this->search("cn=" . $group, "ou=keyserver,ou=groups,dc=ncsu,dc=edu");

        // Strip out only the important stuff
        $result = $result[0]["memberuid"];
        unset($result["count"]);

        return $result;


    }

    /**
     * Convenience funciton to return all groups a user is in
     *
     * @param string $userId - Unity ID of the user in question
     * @return LDAP Result set
     * @throws Ot_Resource_Exception on LDAP Error from Ot_Ldap_Driver::search()
     */
    public function getGroupsForUser($userId)
    {

        // Setup return fields
        $this->addReturnField("memberNisNetgroup");

        $result = $this->search("uid=" . $userId, "ou=accounts,dc=ncsu,dc=edu");

        if (count($result) == 0) {
            return array();
        }

        $result = $result[0]["membernisnetgroup"];

        unset($result["count"]);

        return $result;

    }


    /**
     * Convenience function to return data based on a user id
     *
     * @param string $userId - Users unity id
     * @param bool $allFields - Boolean to return all or just selected fields
     * @return LDAP Result Set
     * @throws Ot_Resource_Exception on LDAP Error from Ot_Ldap_Driver::search()
     */
    public function lookupByUserId($userId, $allFields = false)
    {

        if ($allFields) {
            $this->setReturnFieldsToDefault();
        } else {
            $this->addReturnField("uid");
            $this->addReturnField("cn");
            $this->addReturnField("givenName");
            $this->addReturnField("ncsuMiddleName");
            $this->addReturnField("sn");
            $this->addReturnField("telephoneNumber");
            $this->addReturnField("ncsuPrimaryEmail");
            $this->addReturnField("ncsuPrimaryRole");
            $this->addReturnField("gidNumber");
            $this->addReturnField("ncsuCampusID");
        }

        return $this->search("uid=" . $userId, $this->_defaultContext);
    }


    /**
     * Convenience function to return data based on a campus ID number
     *
     * @param string $cid - Campus ID number
     * @param string $allFields - Boolean to return all or just selected fields
     * @return LDAP Result Set
     * @throws Ot_Resource_Exception on LDAP Error from Ot_Ldap_Driver::search()
     */
    public function lookupByCampusId($cid, $allFields = false)
    {

        if ($allFields) {
            $this->setReturnFieldsToDefault();
        } else {
            $this->addReturnField("uid");
            $this->addReturnField("cn");
            $this->addReturnField("givenName");
            $this->addReturnField("ncsuMiddleName");
            $this->addReturnField("sn");
            $this->addReturnField("telephonenumber");
            $this->addReturnField("ncsuCampusID");
        }

        return $this->search("ncsuCampusID=" . $cid, $this->_defaultContext);
    }


    /**
     * Clean's the LDAP Result Set into an associative array, stripped on any
     * unnecessary stuff.
     *
     * @param array $result - LDAP Result Set
     * @return array
     */
    public function cleanLDAPResult($result)
    {
        unset($result["count"]);

        $ret = array();
        foreach ($result as $r) {
            $temp = array();

            $keys = array_keys($r);

            foreach ($keys as $k) {

                if (!is_int($k) && $k != "dn" && $k != "count") {
                    if (is_array($r[$k])) {
                        $temp[$k] = $r[$k][0];
                    } else {
                        $temp[$k] = $r[$k];
                    }
                }
            }

            $ret[] = $temp;
        }

        return $ret;

    }


    /**
     * Sorts the LDAP Result
     *
     * @param array $result - Cleaned LDAP Result
     * @param string $key - Key to sort on
     * @param string $order - Order to sort ("asc" or "desc")
     * @return sorted array
     */
    public function sortLDAPResult($result, $key, $order = "asc")
    {
       usort($result, create_function('$a, $b', "return strnatcasecmp(\$a['$key'], \$b['$key']);"));

       if ($order == "desc") {
           $result = array_reverse($result);
       }

       return($result);
    }


    /**
     * Builds an associative array based on attribute and value returned
     * in the LDAP result set.
     *
     * @param ldapResult - LDAP result set
     * @return formatted associative array
     * @deprecated - Deprecated as of 1/25/2007 - JFA
     */
    protected function _buildLDAPResultArray($ldapResult)
    {

        $allInfo = array();

        $ct = @ldap_count_entries($this->_link, $ldapResult);

        for ($i = 0; $i < $ct; $i++) {

            $userInfo = array();

            // Basic information
            $userInfo['uid'] = $uid;

            $userInfo['info']['has_account'] = FALSE;
            $accountInfo = array();

            $userInfo['info']['is_employee'] = FALSE;
            $employeeInfo = array();

            $userInfo['info']['is_student'] = FALSE;
            $studentInfo = array();

            for ($entryId = @ldap_first_entry($this->_link, $ldapResult);
                 $entryId != FALSE;
                 $entryId = @ldap_next_entry($this->_link, $entryId)
            ) {

                $thisDn    = @ldap_get_dn($this->_link, $entryId);
                $thisEntry = @ldap_get_attributes($this->_link, $entryId);

                // Parse dn
                $temp = explode(",", $thisDn);
                $checkou = $temp[1];

                switch ($checkou) {

                    case "ou=accounts":
                        $userInfo['info']['has_account'] = TRUE;
                        $dataInfo = &$accountInfo;
                        break;

                    case "ou=employees":
                        $userInfo['info']['is_employee'] = TRUE;
                        $dataInfo = &$employeeInfo;
                        break;

                    case "ou=students":
                        $userInfo['info']['is_student'] = TRUE;
                        $dataInfo = &$studentInfo;
                        break;

                    default:
                        continue 2;
                }

                foreach ($thisEntry as $attribute => $value) {
                    
                    if (!(is_array($value))) {
                        continue;
                    }

                    if ($attribute == "uid") {
                        continue;
                    }

                    if ($attribute == "count") {
                        continue;
                    }

                    if ($value['count'] > 1) {
                          $dataInfo[$attribute] = $value;
                        unset($dataInfo[$attribute]['count']);
                    } else {
                        $dataInfo[$attribute] = $value[0];
                    }
                }
            }


            // Merge information student, then employee, then account

            if ($userInfo['info']['is_student']) {
                $userInfo = array_merge($userInfo, $studentInfo);
                $userInfo['info']['student'] = $studentInfo;
            }

            if ($userInfo['info']['is_employee']) {
                $userInfo = array_merge($userInfo, $employeeInfo);
                $userInfo['info']['employee'] = $employeeInfo;
            }

            if ($userInfo['info']['has_account']) {
                $userInfo = array_merge($userInfo, $accountInfo);
                $userInfo['info']['account'] = $accountInfo;
            }

            $allInfo = $userInfo;
        }

        return $allInfo;

    }

}