<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Claus Due <claus@wildside.dk>, Wildside A/S
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Abstract Flux-enabled controller
 *
 * Extends a traditional ActionController with new services and methods
 * to ease interaction with Flux forms. Is not required as subclass for
 * Controllers rendering records associated with Flux - all it does is
 * ease the interaction by providing a common API.
 *
 * @package Flux
 * @subpackage Controller
 * @route off
 */
class Tx_Flux_Controller_AbstractFluxController extends Tx_Extbase_MVC_Controller_ActionController {

	/**
	 * @var string
	 */
	protected $defaultViewObjectName = 'Tx_Flux_MVC_View_ExposedTemplateView';

	/**
	 * @var Tx_Flux_Provider_ConfigurationService
	 */
	protected $providerConfigurationService;

	/**
	 * @var Tx_Flux_Service_FlexForm
	 */
	protected $flexFormService;

	/**
	 * @var Tx_Flux_Service_Content
	 */
	protected $contentService;

	/**
	 * @var Tx_Flux_Service_Configuration
	 */
	protected $configurationService;

	/**
	 * @var Tx_Flux_Service_DebugService
	 */
	protected $debugService;

	/**
	 * @var Tx_Flux_Provider_ConfigurationProviderInterface
	 */
	protected $provider;

	/**
	 * @var string
	 */
	protected $fluxRecordField = 'pi_flexform';

	/**
	 * @var string
	 */
	protected $fluxTableName = 'tt_content';

	/**
	 * @var array
	 */
	private $setup = array();

	/**
	 * @var array
	 */
	private $data = array();

	/**
	 * @param Tx_Flux_Service_FlexForm $flexFormService
	 * @return void
	 */
	public function injectFlexFormService(Tx_Flux_Service_FlexForm $flexformService) {
		$this->flexFormService = $flexformService;
	}

	/**
	 * @param Tx_Flux_Service_Content $contentService
	 */
	public function injectContentService(Tx_Flux_Service_Content $contentService) {
		$this->contentService = $contentService;
	}

	/**
	 * @param Tx_Flux_Service_Configuration $configurationService
	 */
	public function injectConfigurationService(Tx_Flux_Service_Configuration $configurationService) {
		$this->configurationService = $configurationService;
	}

	/**
	 * @param Tx_Flux_Provider_ConfigurationService $providerConfigurationService
	 * @return void
	 */
	public function injectProviderConfigurationService(Tx_Flux_Provider_ConfigurationService $providerConfigurationService) {
		$this->providerConfigurationService = $providerConfigurationService;
	}

	/**
	 * @param Tx_Flux_Service_DebugService $debugService
	 * @return void
	 */
	public function injectDebugService(Tx_Flux_Service_DebugService $debugService) {
		$this->debugService = $debugService;
	}

	/**
	 * @param Tx_Extbase_MVC_View_ViewInterface $view
	 *
	 * @return void
	 */
	public function initializeView(Tx_Extbase_MVC_View_ViewInterface $view) {
		try {
			$row = $this->configurationManager->getContentObject()->data;
			$table = $this->getFluxTableName();
			$field = $this->getFluxRecordField();
			$extensionName = $this->controllerContext->getRequest()->getControllerExtensionName();
			$extensionKey = t3lib_div::camelCaseToLowerCaseUnderscored($extensionName);
			$extensionSignature = str_replace('_', '', $extensionKey);
			$providers = $this->providerConfigurationService->resolveConfigurationProviders($table, $field, $row);
			$this->provider = $this->providerConfigurationService->resolvePrimaryConfigurationProvider($table, $field, $row);
			if (NULL === $this->provider) {
				$this->debugService->message('Unable to resolve a ConfigurationProvider, but controller indicates it is a Flux-enabled Controller - ' .
					'this is a grave error and indicates that EXT: ' . $extensionName . ' itself is broken - or that EXT:' . $extensionName .
					' has been overridden by another implementation which is broken. The controller that caused this error was ' .
					get_class($this) . ' and the table name is "' . $table . '".', t3lib_div::SYSLOG_SEVERITY_WARNING);
				return;
			}
			$this->setup = $this->provider->getTemplatePaths($row);
			if (FALSE === is_array($this->setup) || 0 === count($this->setup)) {
				throw new Exception('Unable to read a working path set from the Provider. The extension that caused this error was "' .
					$extensionName . '" and the controller was "' . get_class($this) . '". The provider which should have returned ' .
					'a valid path set was "' . get_class($this->provider) . '" but it returned an empty array or not an array. View ' .
					'paths have been reset to paths native to the controller in question.', 1364685651);
			}
			$this->data = $this->provider->getFlexFormValues($row);
			$settings = $this->configurationService->getTypoScriptSubConfiguration(NULL, 'settings', array(), $extensionSignature);
			$templatePathAndFilename = $this->provider->getTemplatePathAndFilename($row);
			if (FALSE === file_exists($templatePathAndFilename)) {
				throw new Exception('Desired template file "' . $templatePathAndFilename . '" does not exist', 1364741158);
			}
			$view->setTemplatePathAndFilename($templatePathAndFilename);
			$view->setLayoutRootPath($this->setup['layoutRootPath']);
			$view->setPartialRootPath($this->setup['partialRootPath']);
			$view->setTemplateRootPath($this->setup['templateRootPath']);
			$view->assignMultiple($this->data);
			$view->assign('settings', $settings);
		} catch (Exception $error) {
			if (TRUE === isset($this->settings['displayErrors']) && 0 < $this->settings['displayErrors']) {
				throw $error;
			}
			$this->debugService->debug($error);
			$view->assign('class', get_class($this));
			$view->assign('error', $error);
			$view->assign('backtrace', $this->getLimitedBacktrace());
			if ('error' !== $this->request->getControllerActionName()) {
				$this->forward('error');
			}
		}
	}

	/**
	 * @return string
	 */
	public function errorAction() {
		$setup = $this->getSetup();
		$extensionName = $this->controllerContext->getRequest()->getControllerExtensionName();
		$extensionKey = t3lib_div::camelCaseToLowerCaseUnderscored($extensionName);
		$nativePaths = $this->configurationService->getViewConfiguration($extensionKey);
		$errorPageSubPath = $controllerObjectName . '/Error.' . $this->request->getFormat();
		$controllerObjectName = $this->request->getControllerObjectName();
		$errorTemplatePathAndFilename = $setup['templateRootPath'] . $errorPageSubPath;
		if (FALSE === file_exists($errorTemplatePathAndFilename) || $setup === NULL) {
			if (TRUE === file_exists($nativePaths['templateRootPath'] . $errorPageSubPath)) {
				$this->view->setTemplateRootPath($nativePaths['templateRootPath']);
				$this->view->setLayoutRootPath($nativePaths['layoutRootPath']);
				$this->view->setPartialRootPath($nativePaths['partialRootPath']);
			}
		}
		return $this->view->render();
	}

	/**
	 * Get the data stored in a record's Flux-enabled field,
	 * i.e. the variables of the Flux template as configured in this
	 * particular record.
	 *
	 * @return array
	 */
	protected function getData() {
		return $this->data;
	}

	/**
	 * Get the array of TS configuration associated with the
	 * Flux template of the record (or overall record type)
	 * currently being rendered.
	 *
	 * @return array
	 */
	protected function getSetup() {
		return $this->setup;
	}

	/**
	 * @return string
	 */
	protected function getFluxRecordField() {
		return $this->fluxRecordField;
	}

	/**
	 * @return string
	 */
	protected function getFluxTableName() {
		return $this->fluxTableName;
	}

	/**
	 * @return array
	 */
	private function getLimitedBacktrace() {
		$trace = debug_backtrace();
		foreach ($trace as $index => $step) {
			if (($step['class'] === 'TYPO3\\CMS\\Extbase\\Core\\Bootstrap' || $step['class'] === 'Tx_Extbase_Core_Bootstrap') && $step['function'] === 'run') {
				$trace = array_slice($trace, 1, $index);
				break;
			}
		}
		return $trace;
	}

}