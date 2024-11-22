<?php

namespace ProcessWire;

class ProcessTextSynthesisConfig extends ModuleConfig {

	public bool $hasLazyCron = false;

	public function __construct() {
		parent::__construct();
		$this->hasLazyCron = modules()->isInstalled('LazyCron');
	}

	/**
	 * @return array
	 * @throws WirePermissionException
	 */
	public function getDefaults(): array {

		// get schedules from Lazy Cron (if installed)
		if($this->hasLazyCron) {
			/** @var LazyCron $lazyCronInstance */
			$lazyCronInstance = modules()->get('LazyCron');
			$getTimeFuncsFunction = function(){ return $this->timeFuncs; };
		}

		return [
			'endpoint' => 'https://texttospeech.googleapis.com/v1beta1/',
			'apiKey' => '',
			'cronSchedule' => 300,
			'cronParallelCalls' => 3,
			'deleteCompleted' => true,
			'deleteCompletedAfter' => 86400,
			'timeFuncs' => $this->hasLazyCron ? $getTimeFuncsFunction->call($lazyCronInstance) : []
		];
	}

	public function getInputfields(): InputfieldWrapper {
		$inputfields = parent::getInputfields();

		$inputfields->add([
			'type' => 'Url',
			'name' => 'endpoint',
			'label' => __('API URL'),
			'description' => __('Insert the Google API URL.'),
			'columnWidth' => 50
		]);

		$inputfields->add([
			'type' => 'text',
			'name' => 'apiKey',
			'label' => __('API Key'),
			'description' => __('Insert your API Key.'),
			'columnWidth' => 50
		]);

		$fieldset = new InputfieldFieldset();
		$fieldset->label = __('LazyCron');

		if($this->hasLazyCron) {

			/** @var InputfieldSelect */
			$fieldset->add([
				'type' => 'Select',
				'name' => 'cronSchedule',
				'label' => __('Schedule'),
				'description' => __('If selected, the LazyCron will process the jobs in the queue.'),
				'options' => $this->get('timeFuncs'),
				'columnWidth' => 50
			]);

			/** @var InputfieldInteger */
			$fieldset->add([
				'type' => 'Integer',
				'name' => 'cronParallelCalls',
				'label' => __('Parallel calls'),
				'min' => 0,
				'max' => 1000,
				'attr' => ['style' => 'width: -webkit-fill-available'],
				'inputType' => 'number',
				'description' => __('Number of job executions per cron call. 0 means all.'),
				'columnWidth' => 50
			]);

		} else {
			$fieldset->description = __('Install the LazyCron core module to automatically process the synthesis queue or use another automatic trigger to call the `ProcessTextSynthesis::runQueue()` function.');
			$fieldset->appendMarkup = '<a href="'.config()->urls->admin . 'module/installConfirm?name=LazyCron'.'" class="">'.__('Click to install LazyCron').'</a>';
		}

		$inputfields->add($fieldset);

		/** @var InputfieldInteger */
		$inputfields->add([
			'type' => 'Checkbox',
			'name' => 'deleteCompleted',
			'label' => __('Should completed jobs be deleted?'),
			'checkboxLabel' => __('Delete completed jobs'),
			'columnWidth' => 50
		]);

		/** @var InputfieldInteger */
		$inputfields->add([
			'type' => 'Integer',
			'name' => 'deleteCompletedAfter',
			'label' => __('Seconds after completed jobs are deleted.'),
			'notes' => __("0 sec. immediately \n3600 sec. 1 hour \n86400 sec. 1 day \n604800 sec. 1 week"),
			'min' => 0,
			'attr' => ['style' => 'width: -webkit-fill-available'],
			'inputType' => 'number',
			'showIf' => 'deleteCompleted=1',
			'columnWidth' => 50
		]);

		return $inputfields;
	}
}