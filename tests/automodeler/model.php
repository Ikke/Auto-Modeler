<?php

include_once 'classes/automodeler/dao/database.php';
include_once 'classes/automodeler/model.php';
include_once 'classes/automodeler/exception.php';

class Test_AutoModeler_Model extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that using the state() method returns the initial state of the model
	 */
	public function test_initial_state_returns_new_state()
	{
		$model = new AutoModeler_Model;

		$this->assertSame($model->state(), AutoModeler_Model::STATE_NEW);
	}

	/**
	 * Tests that assigning a state returns the new state immediately
	 */
	public function test_state_assign_returns_state()
	{
		$model = new AutoModeler_Model;

		$new_state = $model->state(AutoModeler_Model::STATE_LOADING);

		$this->assertSame($new_state, AutoModeler_Model::STATE_LOADING);
	}

	/**
	 * Tests that assigning a new state is returned later
	 */
	public function test_state_assign_returns_later()
	{
		$model = new AutoModeler_Model;

		$model->state(AutoModeler_Model::STATE_LOADING);

		$this->assertSame($model->state(), AutoModeler_Model::STATE_LOADING);
	}

	/**
	 * Tests that a bare model has no data array
	 */
	public function test_bare_model_has_no_data()
	{
		$model = new AutoModeler_Model;

		$this->assertSame($model->as_array(), array());
	}

	/**
	 * Tests that a new data definition can be injected into the model via the constructor
	 */
	public function test_new_data_can_be_injected_via_constructor()
	{
		$model = new AutoModeler_Model(
			array(
				'id',
				'foo',
				'bar',
			)
		);

		$this->assertSame(
			$model->as_array(),
			array(
				'id' => NULL,
				'foo' => NULL,
				'bar' => NULL,
			)
		);
	}

	/**
	 * Tests that we can write validations to a model and read them back.
	 */
	public function test_write_read_validation_rules()
	{
		$model = new AutoModeler_model(
			array(
				'id',
				'foo',
				'bar'
			)
		);

		$rules = array(
			'foo' => array(
				array('not_empty')
			),
			'bar' => array(
				array('numeric')
			)
		);

		$model->rules($rules);

		$this->assertSame($model->rules(), $rules);
	}

	/**
	 * Tests that we can read default data values from a model
	 */
	public function test_read_properties()
	{
		$model = new AutoModeler_Model(
			array(
				'id',
				'foo',
				'bar'
			)
		);

		$this->assertSame($model->id, NULL);
		$this->assertSame($model->foo, NULL);
		$this->assertSame($model->bar, NULL);
	}

	/**
	 * Tests that undefined read requests throw an exception
	 */
	public function test_undefined_read_throws_exception()
	{
		$model = new AutoModeler_Model;

		try
		{
			$id = $model->id;

			$this->fail('Undefined attributes should throw an exception when read!');
		}
		catch (AutoModeler_Exception $e)
		{
			$this->assertSame($e->getMessage(), 'Undefined key: id');
		}
	}

	/**
	 * Tests that we can set model properties
	 */
	public function test_set_model_properties()
	{
		$model = new AutoModeler_Model(
			array(
				'id'
			)
		);

		$model->id = 1;

		$this->assertSame($model->id, 1);
		$this->assertSame($model->as_array(), array('id' => 1));
	}

	/**
	 * Gets a standard model with validation for validation tests
	 *
	 * @return AutoModeler_Model
	 */
	protected function get_model_with_validation()
	{
		$model = new AutoModeler_Model(
			array(
				'id',
				'foo',
				'bar'
			)
		);

		$model->rules(
			array(
				'foo' => array(
					array('not_empty')
				),
				'bar' => array(
					array('numeric')
				)
			)
		);

		$model->foo = 'bar';
		$model->bar = 1;

		return $model;
	}

	/**
	 * Gets a standard validation object
	 *
	 * @return Validation
	 */
	protected function get_default_validation_object()
	{
		$validation = $this->getMock(
			'Validation',
			array('copy', 'as_array', 'rules', 'check', 'bind', 'errors')
		);
		$validation->expects($this->any())
			->method('as_array')
			->will(
				$this->returnValue(array())
			);
		$validation->expects($this->any())
			->method('copy')
			->will(
				$this->returnValue($validation)
			);

		return $validation;
	}

	/**
	 * Tests that we can successfuly validate a model
	 */
	public function test_successfuly_validate_model()
	{
		$model = $this->get_model_with_validation();

		$validation = $this->get_default_validation_object();
		$validation->expects($this->any())
			->method('check')
			->will($this->returnValue(TRUE));

		$status = $model->valid($validation);

		$this->assertSame($status, TRUE);
	}

	/**
	 * Tests that we can successfuly validation an invalid model
	 */
	public function test_error_validate_model()
	{
		$model = $this->get_model_with_validation();
		$model->bar = 'test';

		$validation = $this->get_default_validation_object();

		$validation->expects($this->any())
			->method('check')
			->will($this->returnValue(FALSE));
		$validation->expects($this->any())
			->method('errors')
			->will(
				$this->returnValue(
					array(
						'foo' => 'foo must not be empty',
						'bar' => 'bar must be numeric',
					)
				)
			);

		$status = $model->valid($validation);

		$this->assertTrue(is_array($status), $status);
		$this->assertTrue(array_key_exists('status', $status));
		$this->assertFalse($status['status']);
		$this->assertTrue(array_key_exists('errors', $status));
		$this->assertTrue(is_array($status['errors']));
	}
}
