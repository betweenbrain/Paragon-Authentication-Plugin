<?php defined('_JEXEC') or die;

/**
 * File       paragonauth.php
 * Created    10/31/14 11:11 AM
 * Author     Matt Thomas | matt@betweenbrain.com | http://betweenbrain.com
 * Support    https://github.com/betweenbrain/
 * Copyright  Copyright (C) 2014 betweenbrain llc. All Rights Reserved.
 * License    GNU GPL v2 or later
 */
class plgAuthenticationParagonauth extends JPlugin
{

	/**
	 * Constructor.
	 *
	 * @param   object &$subject The object to observe
	 * @param   array  $config   An optional associative array of configuration settings.
	 *
	 * @since   1.0.0
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		$this->app           = JFactory::getApplication();
		$this->client        = new SoapClient('http://178.251.168.55:8013/ParagonMembershipWeb.svc?wsdl');
		$this->filter        = new JFilterInput;
		$this->membSysConfig = array(
			'membDBConfig' => array(
				'IntegratedSecurity' => 'true',
				'MembDbDataPath'     => 'c:\\sqldata\\',
				'MembDbDatabaseName' => 'MembTrain',
				'MembDbServer'       => 'ROSLSQL02\SqlExpress'
			)
		);

		// Load the language file on instantiation
		$this->loadLanguage();
	}

	/**
	 * This method should handle any authentication and report back to the subject
	 * See libraries/joomla/user/authentication.php for more details.
	 *
	 * @access    public
	 *
	 * @param     array  $credentials Array holding the user credentials ('username' and 'password')
	 * @param     array  $options     Array of extra options
	 * @param     object $response    Authentication response object. See http://api.joomla.org/cms-3/classes/JAuthenticationResponse.html
	 *
	 * @return    boolean
	 * @since     1.5
	 */
	function onUserAuthenticate($credentials, $options, &$response)
	{

		// Check login credentials
		if ($this->checkMemberAuth($credentials['username'], $credentials['password'])->CheckMemberAuthResult)
		{
			$member = $this->getMemberDetails($credentials['username'])->getMemberDetailsStats2Result;

			$response->email    = trim($member->Email);
			$response->fullname = trim($member->Forename) . ' ' . trim($member->Surname);
			$response->status   = JAuthentication::STATUS_SUCCESS;
			$response->type     = 'Paragon';
			$response->username = $this->filter->clean($credentials['username']);
			$response->password = $this->filter->clean($credentials['password']);

			return true;
		}

		// Invalid credentials
		$response->status        = JAuthentication::STATUS_FAILURE;
		$response->error_message = JText::_('JGLOBAL_AUTH_INVALID_PASS');

		return true;
	}

	/**
	 * Authenticate a user against the API
	 *
	 * @param $username
	 * @param $password
	 *
	 * @return mixed
	 */
	private function checkMemberAuth($username, $password)
	{
		$params = array(
			'membSysConfig'    => $this->membSysConfig,
			'membLoginDetails' => array(
				'MemberNumber'          => '',
				'IndividualNumber'      => '',
				'UseAlternateSearchKey' => true,
				'AlternateSearchKey'    => $username,
				'InternetPassword'      => $password
			)
		);

		return $this->client->CheckMemberAuth($params);
	}

	/**
	 * Retrieves the user details object from the API
	 *
	 * @param $username
	 *
	 * @return mixed
	 */
	private function getMemberDetails($username)
	{
		$params = array(
			'membSysConfig' => $this->membSysConfig,
			'Stats2'        => $username
		);

		return $this->client->getMemberDetailsStats2($params);
	}

}
