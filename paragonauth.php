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
		$this->client        = new SoapClient($this->params->get('client'));
		$this->filter        = new JFilterInput;
		$this->membSysConfig = array(
			'membDBConfig' => array(
				'IntegratedSecurity' => $this->params->get('integratedSecurity'),
				'MembDbDataPath'     => $this->params->get('dbPath'),
				'MembDbDatabaseName' => $this->params->get('dbName'),
				'MembDbServer'       => $this->params->get('dbServer')
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
			$memberDetails = $this->memberDetails($credentials['username']);

			// Deny access if member status is dormant or frozen
			if (strtolower($memberDetails->Status) == 'd' || strtolower($memberDetails->Status) == 'f')
			{
				$response->status        = JAuthentication::STATUS_DENIED;
				$response->error_message = JText::_('JERROR_NOLOGIN_BLOCKED');

				return true;
			}

			$response->email    = trim($memberDetails->Email);
			$response->fullname = trim($memberDetails->Forename) . ' ' . trim($memberDetails->Surname);
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
	private function memberDetails($username)
	{
		$params = array(
			'membSysConfig' => $this->membSysConfig,
			'Stats2'        => $username
		);

		return $this->client->getMemberDetailsStats2($params)->getMemberDetailsStats2Result;
	}

}
