<?php
/**
 * COPYRIGHT NOTICE
 * Copyright (c) 2021 Neue Rituale GbR
 * @author NR <code@neuerituale.com>
 */

namespace ProcessWire;

/**
 * @global array $jobs
 * @global ProcessTextSynthesis $processInstance
 * @global Config $config
 * @global WireCache $cache
 * @global WireDateTime $datetime
 * @global Modules $modules
 */

/**
 * @param \stdClass $request
 * @return string
 */
function buildConfigString(\stdClass $request): string {

	$properties = [];

	// voice
	if(property_exists($request, 'voice')) {
		if(property_exists($request->voice, 'languageCode')) $properties[__('Language')] = $request->voice->languageCode;
		if(property_exists($request->voice, 'name')) $properties[__('Name')] = $request->voice->name;
		if(property_exists($request->voice, 'ssmlGender')) $properties[__('Gender')] = $request->voice->ssmlGender;
		if(property_exists($request->voice, 'naturalSampleRateHertz')) $properties[__('Natural samplerate')] = $request->voice->naturalSampleRateHertz;
	}

	// audio
	if(property_exists($request, 'audioConfig')) {
		if(property_exists($request->audioConfig, 'audioEncoding')) $properties[__('Encoding')] = $request->audioConfig->audioEncoding;
		if(property_exists($request->audioConfig, 'speakingRate')) $properties[__('Speed')] = $request->audioConfig->speakingRate;
		if(property_exists($request->audioConfig, 'pitch')) $properties[__('Pitch')] = $request->audioConfig->pitch;
		if(property_exists($request->audioConfig, 'volumeGainDb')) $properties[__('Volume')] = $request->audioConfig->volumeGainDb;
		if(property_exists($request->audioConfig, 'sampleRateHertz')) $properties[__('Samplerate')] = $request->audioConfig->sampleRateHertz . ' Hz';
	}

	$out = '';
	foreach($properties as $name => $value) $out .= $name . ': ' . $value . "<br> ";
	return rtrim($out, '<br> ');
}

?>

<?php if(!count($jobs)) : ?>
	<div class="uk-card uk-card-primary uk-margin">
		<div>
			<div class="uk-card-body uk-text-lead uk-text-muted uk-padding-small uk-text-center">
				<?= __('No jobs in queue.'); ?>
			</div>
		</div>
	</div>
<?php else :

	// build table
	/** @var MarkupAdminDataTable $table */
	$table = wire()->modules->get('MarkupAdminDataTable');
	$table->headerRow([
		__('ID'),
		__('Page'),
		__('Field'),
		__('Mode'),
		__('Details'),
		__('Status'),
		__('Completed'),
		__('Created'),
		__('Actions')
	]);
	$table->encodeEntities = false;
	$table->addClass('uk-table-striped');
	$table->addClass('uk-table-middle');
	$table->removeClass('uk-table-justify');

	/** @var \stdClass $job */
	foreach ($jobs as $job) {

		$row = [];
		$rowClasses = [];

		/** @var Field $jobField */
		$jobField = fields()->get($job->field);

		/** @var Page $jobPage */
		$jobPage = pages()->getOneById($job->pages_id);

		/** @var \stdClass $request */
		$request = json_decode($job->request);
		if(!$request) continue;

		// row status
		if($job->error) $rowClasses[] = 'failed';
		if($job->completed) $rowClasses[] = 'completed';

		// Id
		$row[] = '#' . $job->id;

		// Page
		$row[] = [$jobPage->get('title|name') => $jobPage->editUrl];

		// Field
		$row[] = [$jobField->get('label|name') => $jobField->editUrl() . '#inputfieldConfig'];

		// Mode
		$row[] = property_exists($request->input, 'ssml')
			? '<span uk-tooltip="title:'.__('Speech Synthesis Markup Language (SSML) is used to build the field content.').'">SSML</span>'
			: '<span uk-tooltip="title:'.__('All fields are lined up').'">Text</span>'
		;

		// Config
		$configStr = buildConfigString($request);
		$row[] = ['<span uk-tooltip="title:'.$configStr.'" uk-icon="icon: info;"></span>', 'uk-text-truncate'];

		// Status
		if(!empty($job->error)) {
			$row[] = '<span class="uk-badge uk-padding-small uk-label-danger" uk-tooltip="title: '.$job->error.'">'.__('Error').'</span>';
		} else if($job->completed) {
			$row[] = '<span class="uk-badge uk-padding-small uk-label-success">'.__('Completed').'</span>';
		} else {
			$row[] = '<span class="uk-badge uk-padding-small">'.__('Waiting').'</span>';
		}

		// Completed
		if(!empty($job->completed)) {
			$triggerInfo = $datetime->formatDate($job->completed, $config->dateFormat);
			$row[] = '<span style="font-size:0;">'.strtotime($job->completed).'</span> ' . '<span uk-tooltip="title:'.$triggerInfo.'">'.$datetime->formatDate($job->completed, 'relative').'</span>';
		} else {
			$row[] = '<span style="font-size:0;">0</span> – ';
		}

		// Created
		$triggerInfo = $datetime->formatDate($job->created, $config->dateFormat);
		$row[] = '<span style="font-size:0;">'.strtotime($job->created).'</span> ' . '<span uk-tooltip="title:'.$triggerInfo.'">'.$datetime->formatDate($job->created, 'relative').'</span>';

		// Action
		$actions = [];

			// Run
			if($job->completed || $job->error) {
				$call = "ProcessWire.confirm(
					'".__('Would you like to do this job again?')."',
					() => window.location.replace('./run/".$job->id."/'),
					() => {}
				);";

				$buttonClass = $job->error ? 'uk-button-danger' : 'uk-button-success';
				$actions[] = '<a href="javascript:' . $call . '" uk-tooltip="title:'
					.__('Execute this job again').'" uk-icon="refresh" class="uk-icon-button '.$buttonClass.'"></a>';
			} else {
				$actions[] = '<a href="./run/'.$job->id.'/" uk-tooltip="title:'
					.__('Execute this job').'" uk-icon="play" class="uk-icon-button uk-button-primary"></a>';
			}

			// delete
			$call = "ProcessWire.confirm(
				'".__('Do you really want to delete this job?')."',
					() => window.location.replace('./delete/".$job->id."/'),
					() => {}
				);";

			$actions[] = '<a href="javascript:'.$call.'" uk-tooltip="title:'
				.__('Delete this job').'" uk-icon="trash" class="uk-icon-button uk-button-default"></a>';

		$row[] = '<div class="uk-flex" style="gap:.25em">' . implode('', $actions) . '</div>';

		// Add
		$table->row($row, ['class' => implode(' ', $rowClasses)]);
	}

	echo $table->render();

// last run
$lastRun = $cache->getFor(ProcessTextSynthesis::cacheNs, 'lastRun');
$lastRunStr = $lastRun > 0 ? $datetime->formatDate($lastRun, $config->dateFormat) : __('Never');

?>
<div class="uk-flex uk-flex-between uk-flex-wrap uk-text-small uk-text-muted uk-margin-top">
	<div><?= sprintf(__('Last run: %s'), $lastRunStr); ?></div>

	<?php if($processInstance->deleteCompleted) : ?>

		<?php if($processInstance->deleteCompletedAfter > 0) :
			$tooltip = sprintf(__('%d seconds'), $processInstance->deleteCompletedAfter);
			$label = sprintf(
				__('Completed jobs are deleted after %s'),
				$datetime->relativeTimeStr($processInstance->deleteCompletedAfter + time(), true, false)
			);
			?>
			<div uk-tooltip="title:<?= $tooltip ?>"><?= $label ?></div>
		<?php else : ?>
			<div><?= __('Completed jobs are deleted immediately'); ?></div>
		<?php endif; ?>

	<?php else : ?>
		<div><?= __('Completed jobs are not deleted.'); ?></div>
	<?php endif; ?>

</div>

<?php

/** @var InputfieldWrapper $wrapper */
$wrapper = $modules->get('InputfieldWrapper');

/** @var $button InputfieldButton */
$button = $modules->get('InputfieldButton');
$button
	->set('name', '_clear_queue')
	->set('id', '_clear_queue')
	->set('value', __('Clear Queue'))
	->set('icon', 'trash')
	->attr(
		'onclick',
		"ProcessWire.confirm(
			'".__('Want to remove all jobs?')."',
			() => window.location.replace('./delete/all/'),
			() => {}
		)"
	)
;

// Pending
if(array_filter($jobs, fn($job) => !$job->completed && !$job->error)) {
	$button->addActionLink(
		"javascript:ProcessWire.confirm('".__('Want to remove pending jobs?')."',() => window.location.replace('./delete/pending/'),() => {})",
		__('Clear pending jobs'),
		'trash'
	);
}

// Completed
if(array_filter($jobs, fn($job) => $job->completed)) {
	$button->addActionLink(
		"javascript:ProcessWire.confirm('".__('Want to remove the completed jobs?')."',() => window.location.replace('./delete/completed/'),() => {})",
		__('Clear completed jobs'),
		'trash'
	);
}

// Error
if(array_filter($jobs, fn($job) => $job->error)) {
	$button->addActionLink(
		"javascript:ProcessWire.confirm('".__('Want to remove the failed jobs?')."',() => window.location.replace('./delete/error/'),() => {})",
		__('Clear failed jobs'),
		'trash'
	);
}

$wrapper->add($button);
echo $wrapper->render();

endif;