<?php
/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace PatchTester\Controller;

use PatchTester\Model\PullsModel;

/**
 * Controller class to fetch remote data
 *
 * @since  2.0
 */
class FetchController extends DisplayController
{
	/**
	 * Execute the controller.
	 *
	 * @return  void  Redirects the application
	 *
	 * @since   2.0
	 */
	public function execute()
	{
		// We don't want this request to be cached.
		header('Pragma: no-cache');
		header('Cache-Control: no-cache');
		header('Expires: -1');

		try
		{
			// Fetch our page from the session
			$page = \JFactory::getSession()->get('com_patchtester_fetcher_page', 1);

			// TODO - Decouple the model and context?
			$model = new PullsModel('com_patchtester.fetch', null, \JFactory::getDbo());

			// Initialize the state for the model
			$model->setState($this->initializeState($model));

			$status = $model->requestFromGithub($page);
		}
		catch (\Exception $e)
		{
			$response = new \JResponseJson($e);

			echo json_encode($response);

			$this->getApplication()->close(1);
		}

		// Update the UI and session now
		if (isset($status['page']))
		{
			\JFactory::getSession()->set('com_patchtester_fetcher_page', $status['page']);
			$message = \JText::sprintf('COM_PATCHTESTER_FETCH_PAGE_NUMBER', $status['page']);
			unset($status['page']);
		}
		else
		{
			$status['header'] = \JText::_('COM_PATCHTESTER_FETCH_SUCCESSFUL', true);
			$message = \JText::_('COM_PATCHTESTER_FETCH_COMPLETE_CLOSE_WINDOW', true);
		}

		$response = new \JResponseJson($status, $message, false, true);

		echo json_encode($response);

		$this->getApplication()->close();
	}
}