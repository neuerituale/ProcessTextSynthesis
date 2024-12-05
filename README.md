# ProcessTextSynthesis

## What it does

The module can help you to make your website content more accessible. It synthesis text fields via the Google Text2Speech Api. Synthesis jobs are placed in a queue and processed one after the other. The text fields can be configured by selection or via SSML. Voice, language and speed are configured via JSON. 

The module's queue supports LazyCron, but can also be triggered manually.

## Features
- Simple integration via the native file field.
- Support [SSML](https://en.wikipedia.org/wiki/Speech_Synthesis_Markup_Language).
- Direct synthesis via save action
- Queue for many synthesis jobs
- Process page for an overview of the queue

## Install

1. Copy the files for this module to /site/modules/ProcessTextSynthesis/
3. In admin: Modules > Refresh. Install ProcessTextSynthesis.
4. Create a new file field of the type ‘TextSynthesis’.
5. Configure the text synthesis in the Input tab. More on this in the ‘Configuration’ section.

## Install via composer
1. Execute the following command in your website root directory.
   ```bash
   composer require nr/processtextsynthesis
   ```

## Process view
![ProcessView](https://private-user-images.githubusercontent.com/11630948/393030336-272aa8c5-a54d-4097-a996-a9519ad8922f.png?jwt=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJnaXRodWIuY29tIiwiYXVkIjoicmF3LmdpdGh1YnVzZXJjb250ZW50LmNvbSIsImtleSI6ImtleTUiLCJleHAiOjE3MzM0NDAwMTAsIm5iZiI6MTczMzQzOTcxMCwicGF0aCI6Ii8xMTYzMDk0OC8zOTMwMzAzMzYtMjcyYWE4YzUtYTU0ZC00MDk3LWE5OTYtYTk1MTlhZDg5MjJmLnBuZz9YLUFtei1BbGdvcml0aG09QVdTNC1ITUFDLVNIQTI1NiZYLUFtei1DcmVkZW50aWFsPUFLSUFWQ09EWUxTQTUzUFFLNFpBJTJGMjAyNDEyMDUlMkZ1cy1lYXN0LTElMkZzMyUyRmF3czRfcmVxdWVzdCZYLUFtei1EYXRlPTIwMjQxMjA1VDIzMDE1MFomWC1BbXotRXhwaXJlcz0zMDAmWC1BbXotU2lnbmF0dXJlPTM4MDFiMzhhZGYwZjA4OTI4YTU1OTI4MmRkN2ExMTI1MTg1OTQwZmQxYjY3ZGMyNzFiNWMwYTYwOWI4NWEyZDEmWC1BbXotU2lnbmVkSGVhZGVycz1ob3N0In0.ac9RnkkREZwHXRMbgOy4g6dY8rTBuqBDl3WsvZxiKtU)

## Field configuration

`Setup` > `Fields` > `my_synthesis_field`

Switch to the Input tab to configure the field. You can use text mode and select the text fields to synthesise. The selected fields will appear in a row. In SSML mode, you can change the emphasis, speed or pitch of individual text fields. You can also add background noise or ambience (`<par>`). You can find out more about this in the `SSML Example` fieldset.

Here are all the supported [markup tags](https://cloud.google.com/text-to-speech/docs/ssml) for the Google Cloud API.

### Request

Adjust voice, language and audio parameters such as pitch, encoding and sample rate.
All setting options can be found in the request documentation. Note that in SSML mode you must define pitch and rate with an enclosing `<prosody>` tag.

```xml
<prosody rate="0.85" pitch="0.45">{my_field_name}</prosody>
<!-- default rate: 1, default pitch: 0.5 -->
```

## Module configuration

`Modules` > `Configure` > `ProcessTextSynthesis`

### API URL
Here you can customise the URL for the Google endpoint. The beta url is entered here by default.

### API Key
Enter your API key here. If you do not yet have an API key, you can generate one in the [Credentials area](https://console.cloud.google.com/apis/credentials) of the Google Cloud Console.

### LazyCron (Cron)
The queue can be started via the LazyCron module. The module must be installed for this. After installation, the schedule and the jobs to be executed in parallel can be configured.

Alternatively, the queue can also be executed via a real cronjob and by calling the method `ProcessTextSynthesis::runQueue(int $parallelJobs)`.

```php
/** @var ProcessTextSynthesis $processTextSynthesis */
$processTextSynthesis = wire()->modules->get('ProcessTextSynthesis');
if($processTextSynthesis instanceof ProcessTextSynthesis) $processTextSynthesis->runQueue(1);
// runQueue() accepts the number of jobs to be executed in parallel as parameter
```

### Should completed jobs be deleted?
Once a synthesis job has been completed, it can be deleted immediately or after the specified time if required.

The `ProcessTextSynthesis::runMaintenance()` method is then executed after `ProcessTextSynthesis::runQueue()`.

## TODO
Add multilanguage support (`my_synthesis_field`, `my_synthesis_field_fr`, `my_synthesis_field_de`)