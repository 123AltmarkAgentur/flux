<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Claus Due <claus@wildside.dk>
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
 * ************************************************************* */

/**
 * @author Claus Due <claus@wildside.dk>
 * @package Flux
 */
class Tx_Flux_Form_Field_FileTest extends Tx_Flux_Tests_Functional_Form_Field_AbstractFieldTest {

	/**
	 * @var array
	 */
	protected $chainProperties = array(
		'name' => 'test',
		'label' => 'Test field',
		'enable' => TRUE,
		'maxSize' => 135153542,
		'allowed' => 'jpg,gif',
		'disallowed' => 'doc,docx',
		'uploadFolder' => '',
		'showThumbnails' => TRUE
	);

	/**
	 * @test
	 */
	public function canSetDefaultValueFromSimpleString() {
		$instance = Tx_Flux_Form::create(array())->createField('File', 'file');
		$defaultValue = 'testfile.jpg';
		$instance->setDefault($defaultValue);
		$this->assertSame($defaultValue . '|' . $defaultValue, $instance->getDefault());
	}

	/**
	 * @test
	 */
	public function canSetDefaultValueFromAlreadyCorrectString() {
		$instance = Tx_Flux_Form::create(array())->createField('File', 'file');
		$defaultValue = 'testfile.jpg|testfile.jpg';
		$instance->setDefault($defaultValue);
		$this->assertSame($defaultValue, $instance->getDefault());
	}

	/**
	 * @test
	 */
	public function canSetDefaultValueFromCsvOfSimpleStrings() {
		$instance = Tx_Flux_Form::create(array())->createField('File', 'file');
		$defaultValue = 'testfile1.jpg,testfile2.jpg';
		$expected = 'testfile1.jpg|testfile1.jpg,testfile2.jpg|testfile2.jpg';
		$instance->setDefault($defaultValue);
		$this->assertSame($expected, $instance->getDefault());
	}

	/**
	 * @test
	 */
	public function canSetDefaultValueFromCsvfAlreadyCorrectStrings() {
		$instance = Tx_Flux_Form::create(array())->createField('File', 'file');
		$defaultValue = 'testfile1.jpg|testfile1.jpg,testfile2.jpg|testfile2.jpg';
		$instance->setDefault($defaultValue);
		$this->assertSame($defaultValue, $instance->getDefault());
	}

}
