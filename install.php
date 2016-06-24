<?php
/*
 * *************************************************************************************************************************
 * * CORAL Unified Installer v. 0.1.0
 * *
 * * Copyright (c) 2010 University of Notre Dame
 * *
 * * This file is part of CORAL.
 * *
 * * CORAL is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * *
 * * CORAL is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * *
 * * You should have received a copy of the GNU General Public License along with CORAL. If not, see <http://www.gnu.org/licenses/>.
 * *
 * *************************************************************************************************************************
 */

 /**
 * @author j3frea+coral@gmail.com
 */

// TODO: go through template.php and remove hard coded vars

const INSTALLATION_IN_PROGRESS = true;
require "install/test_if_installed.php";
/**
 *  All the requests that come from the template page post { "installing":true }
 *  So if it's not set, we need to draw the template for the first time.
 */
if (!isset($_POST["installing"]))
{
	// TODO: decide whether to clear the session when navigation to this page does not contain the POST var...
	// (which is always sent on installation internal requests)
	// session_start();
	// session_unset();
	require "install/templates/install_page_template.php";
	draw_install_page_template();
	exit();
}

require "install/test_results_yielder.php";

require "install/installer.php";
$installer = new Installer();
$requirements = $installer->getCheckListUids();

foreach ($requirements as $i => $requirement) {
	if (!$installer->isRequired($requirement))
		continue;

	$testResult = $installer->runTestForResult($requirement);

	if (isset($testResult->skipped))
	{
		switch ($testResult->cause)
		{
			case Installer::CAUSE_ALREADY_EXISTED:
				continue 2; // break switch & continue foreach
				break;
			case Installer::CAUSE_DEPENDENCY_NOT_FOUND:
				$testResult->yield = new stdClass();
				$testResult->yield->messages = [ sprintf(_("Dependency for '%s' not found: %s"), $installer->getTitleFromUid($requirement), $testResult->missing_dependency) ];
				yield_test_results_and_exit($testResult->yield, $installer->getSuccessfullyCompletedTestTitles(), $installer->getApproxiamateCompletion());
				break;
		}
	}
	else if (!$testResult->success)
	{
		$installer_messages = $installer->getMessages();
		$test_messages = isset($testResult->yield->messages) ? $testResult->yield->messages : [];
		$testResult->yield->messages = array_merge($installer_messages, $test_messages);
		yield_test_results_and_exit($testResult->yield, $installer->getSuccessfullyCompletedTestTitles(), $installer->getApproxiamateCompletion());
	}
	else
	{
		if (isset($testResult->completionMessages))
			$completionMessages[ $requirement ] = $testResult->completionMessages;
	}
}

$installer->declareInstallationComplete();

$completed_tests = $installer->getSuccessfullyCompletedTestTitles();
while ($failingPostInstallationTest = $installer->postInstallationTest())
	yield_test_results_and_exit($failingPostInstallationTest->yield, $completed_tests, 95/100);

yield_test_results_and_exit($installer->successful_install(), $completed_tests, 100/100);
