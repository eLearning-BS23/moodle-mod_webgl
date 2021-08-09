<?php
/**
 * webgl activity form
 *
 * @package mod_webgl
 * @copyright  2020 Brain station 23 ltd <>  {@link https://brainstation-23.com/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once ($CFG->dirroot . '/course/moodleform_mod.php');

class mod_webgl_mod_form extends moodleform_mod {
    const STORAGE_ENGINE_AZURE = 1;

    const STORAGE_ENGINE_S3 = 2;

    const STORAGE_ENGINE_S3_DEFAULT_LOCATION = 'ap-southeast-1';

    public function definition() {
        global $CFG, $DB;
        $mform = $this->_form;

        // -------------------------------------------------------
        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        // ... $mform->addHelpButton('name', 'appstreamname', 'webgl');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        // WebGl contetn form portion goes here.
        $mform->addElement('header', 'webglcontent', get_string('header:content', 'webgl'));

        $isupdateform = $this->optional_param('update', 0, PARAM_INT);

        if ($isupdateform > 0) {
            $dataforform = $DB->get_record('course_modules', array('id' => $isupdateform));
            $moduledata = $DB->get_record('webgl', array('id' => $dataforform->instance));
            if ($moduledata->store_zip_file) {
                $filename = str_replace('index.html', $moduledata->webgl_file, $moduledata->index_file_url);
                $ancor = '<div id="fitem_id_webgl_file" class="form-group row  fitem">
                        <div class="col-md-3">
                            <label class="col-form-label d-inline " for="id_webgl_file">&nbsp;</label>
                        </div>
                        <div class="col-md-9 form-inline felement" data-fieldtype="text" id="id_webgl_file">
                            <a target="_blank" href="' . $filename . '">Download ' . $moduledata->webgl_file . '</a>
                        </div>
                    </div>';
            } else {
                $ancor = '<div id="fitem_id_webgl_file" class="form-group row  fitem">
                        <div class="col-md-3">
                            <label class="col-form-label d-inline " for="id_webgl_file">&nbsp;</label>
                        </div>
                        <div class="col-md-9 form-inline felement" data-fieldtype="text" id="id_webgl_file">
                            <p>Previously Uploaded file name : ' . $moduledata->webgl_file . '</p>
                        </div>
                    </div>';
            }
            $mform->addElement('html', $ancor);

        }

        $mform->addElement('filepicker', 'importfile', get_string('input:file', 'webgl'), null, ['accepted_types' => '.zip']);
        $mform->addHelpButton('importfile', 'ziparchive', 'webgl');

        if ($isupdateform > 0) {
            $mform->addElement('advcheckbox', 'update_webgl_content', get_string('content_advcheckbox', 'webgl'));
            $mform->addHelpButton('update_webgl_content', 'content_advcheckbox', 'webgl');
            $mform->disabledIf('importfile', 'update_webgl_content');
        } else {
            $mform->addRule('importfile', null, 'required');
        }

        $mform->addElement('text', 'iframe_height', get_string('iframe_height', 'webgl'));
        $mform->setType('iframe_height', PARAM_TEXT);
        $mform->addHelpButton('iframe_height', 'iframe_height', 'webgl');
        $mform->addRule('iframe_height', null, 'required', null, 'client');
        $iframeheight = get_config('webgl', 'iframe_height');
        $mform->setDefault('iframe_height', $iframeheight);

        $mform->addElement('text', 'iframe_width', get_string('iframe_width', 'webgl'));
        $mform->setType('iframe_width', PARAM_TEXT);
        $mform->addHelpButton('iframe_width', 'iframe_width', 'webgl');
        $mform->addRule('iframe_width', null, 'required', null, 'client');
        $iframewidth = get_config('webgl', 'iframe_width');
        $mform->setDefault('iframe_width', $iframewidth);

        $mform->addElement('advcheckbox', 'before_description', get_string('before_description', 'webgl'));
        $mform->addHelpButton('before_description', 'before_description', 'webgl');
        $mform->addRule('before_description', null, 'required', null, 'client');

        // Storage form fields goes here.
        $mform->addElement('header', 'storage', get_string('storage', 'webgl'));

        $mform->addElement('select', 'storage_engine', get_string('storage_engine', 'webgl'), [
            1 => 'Azure BLOB storage',
            2 => 'AWS Simple Cloud Storage (S3)',
        ]);
        $mform->addHelpButton('storage_engine', 'storage_engine', 'webgl');
        $mform->addRule('storage_engine', null, 'required', null, 'client');
        $storageengine = get_config('webgl', 'storage_engine');
        $mform->setDefault('storage_engine', $storageengine);

        $mform->addElement('text', 'account_name', get_string('account_name', 'webgl'));
        $mform->setType('account_name', PARAM_TEXT);
        $mform->addHelpButton('account_name', 'account_name', 'webgl');
        // $mform->addRule('account_name', null, 'required', null, 'client');
        $accountname = get_config('webgl', 'AccountName');
        $mform->setDefault('account_name', $accountname);

        $mform->addElement('text', 'account_key', get_string('account_key', 'webgl'));
        $mform->setType('account_key', PARAM_TEXT);
        $mform->addHelpButton('account_key', 'account_key', 'webgl');
        // $mform->addRule('account_key', null, 'required', null, 'client');
        $accountkey = get_config('webgl', 'AccountKey');
        $mform->setDefault('account_key', $accountkey);

        $mform->addElement('text', 'container_name', get_string('container_name', 'webgl'));
        $mform->setType('container_name', PARAM_TEXT);
        $mform->addHelpButton('container_name', 'container_name', 'webgl');
        // $mform->addRule('container_name', null, 'required', null, 'client');
        $containername = get_config('webgl', 'ContainerName');
        $mform->setDefault('container_name', $containername);

        $mform->hideIf('account_name', 'storage_engine', 'eq', '2');
        $mform->hideIf('account_key', 'storage_engine', 'eq', '2');
        $mform->hideIf('container_name', 'storage_engine', 'eq', '2');
        $mform->disabledIf('account_name', 'storage_engine', 'eq', '2');
        $mform->disabledIf('account_key', 'storage_engine', 'eq', '2');
        $mform->disabledIf('container_name', 'storage_engine', 'eq', '2');

        $mform->addElement('text', 'access_key', get_string('access_key', 'webgl'));
        $mform->setType('access_key', PARAM_TEXT);
        $mform->addHelpButton('access_key', 'access_key', 'webgl');
        // $mform->addRule('access_key', null, 'required', null, 'client');
        $accesskey = get_config('webgl', 'access_key');
        $mform->setDefault('access_key', $accesskey);

        $mform->addElement('text', 'secret_key', get_string('secret_key', 'webgl'));
        $mform->setType('secret_key', PARAM_TEXT);
        $mform->addHelpButton('secret_key', 'secret_key', 'webgl');
        // $mform->addRule('secret_key', null, 'required', null, 'client');
        $secretkey = get_config('webgl', 'secret_key');
        $mform->setDefault('secret_key', $secretkey);

        $endpointselect = require ('classes/possible_end_points.php');
        $mform->addElement('select', 'endpoint', get_string('endpoint', 'webgl'), $endpointselect);
        $mform->setDefault('endpoint', 's3.amazonaws.com'); // Default to US Endpoint.

        $mform->hideIf('access_key', 'storage_engine', 'eq', '1');
        $mform->hideIf('secret_key', 'storage_engine', 'eq', '1');
        $mform->hideIf('endpoint', 'storage_engine', 'eq', '1');
        $mform->disabledIf('access_key', 'storage_engine', 'eq', '1');
        $mform->disabledIf('secret_key', 'storage_engine', 'eq', '1');
        $mform->disabledIf('endpoint', 'storage_engine', 'eq', '1');

        $mform->addElement('advcheckbox', 'store_zip_file', get_string('store_zip_file', 'webgl'));
        $mform->addHelpButton('store_zip_file', 'store_zip_file', 'webgl');
        $mform->addRule('store_zip_file', null, 'required', null, 'client');
        $storezipfile = get_config('webgl', 'store_zip_file');
        $mform->setDefault('store_zip_file', $storezipfile);

        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    /**
     * @param array $data
     * @param array $files
     * @return array
     * @throws coding_exception
     */
    public function validation($data, $files) {
        $error = [];
        if ($data['storage_engine'] == self::STORAGE_ENGINE_AZURE) {
            if (empty($data['account_name'])) {
                $error['account_name'] = get_string('account_name_error', 'mod_webgl');
            }
            if (empty($data['account_key'])) {
                $error['account_key'] = get_string('account_key_error', 'mod_webgl');
            }
            if (empty($data['container_name'])) {
                $error['container_name'] = get_string('container_name_error', 'mod_webgl');
            }
        } elseif ($data['storage_engine'] == self::STORAGE_ENGINE_S3) {
            if (empty($data['access_key'])) {
                $error['access_key'] = get_string('access_key_error', 'mod_webgl');
            }
            if (empty($data['secret_key'])) {
                $error['secret_key'] = get_string('secret_key_error', 'mod_webgl');
            }
            if (empty($data['endpoint'])) {
                $error['endpoint'] = get_string('endpoint_error', 'mod_webgl');
            }
        }
        return $error;
    }

}