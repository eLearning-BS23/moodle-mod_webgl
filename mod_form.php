<?php

/**
 * webgl activity form
 *
 * @package mod_webgl
 * @copyright  2020 Brain station 23 ltd <>  {@link https://brainstation-23.com/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_webgl_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG, $DB;
        $mform = $this->_form;

        //-------------------------------------------------------
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
        //$mform->addHelpButton('name', 'appstreamname', 'webgl');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        // WebGl contetn form portion goes here.
        $mform->addElement('header', 'webglcontent', get_string('header:content', 'webgl'));

        $is_update_form = $this->optional_param('update',0,PARAM_INT);

        if ($is_update_form > 0) {
            $dataforform = $DB->get_record('course_modules', array('id' => $is_update_form));
            $moduledata = $DB->get_record('webgl', array('id' => $dataforform->instance));
            $ancor = '<div id="fitem_id_webgl_file" class="form-group row  fitem">
                        <div class="col-md-3">
                            <label class="col-form-label d-inline " for="id_webgl_file">&nbsp;</label>
                        </div>
                        <div class="col-md-9 form-inline felement" data-fieldtype="text" id="id_webgl_file">
                            <a target="_blank" href="/mod/webgl/download.php?id='.$is_update_form.'">Download '.$moduledata->webgl_file.'</a>
                        </div>
                    </div>';
            $mform->addElement('html', $ancor);
        }


        $mform->addElement('filepicker', 'importfile', get_string('input:file', 'webgl'),null, ['accepted_types' => '.zip']);
        $mform->addHelpButton('importfile', 'ziparchive', 'webgl');

        if ($is_update_form > 0){
            $mform->addElement('advcheckbox', 'update_webgl_content', get_string('content_advcheckbox','webgl'));
            $mform->addHelpButton('update_webgl_content', 'content_advcheckbox','webgl');
            $mform->disabledIf('importfile','update_webgl_content');
        }else{
            $mform->addRule('importfile', null, 'required');
        }

        // Storage form fields goes here.
        $mform->addElement('header', 'storage', get_string('storage', 'webgl'));

        $mform->addElement('text', 'account_name', get_string('account_name', 'webgl'));
        $mform->setType('account_name', PARAM_TEXT);
        $mform->addHelpButton('account_name', 'account_name', 'webgl');
        $mform->addRule('account_name', null, 'required', null, 'client');
        $AccountName = get_config('webgl','AccountName');
        $mform->setDefault('account_name',$AccountName);

        $mform->addElement('text', 'account_key', get_string('account_key', 'webgl'));
        $mform->setType('account_key', PARAM_TEXT);
        $mform->addHelpButton('account_key', 'account_key', 'webgl');
        $mform->addRule('account_key', null, 'required', null, 'client');
        $AccountKey = get_config('webgl','AccountKey');
        $mform->setDefault('account_key',$AccountKey);

        $mform->addElement('text', 'container_name', get_string('container_name', 'webgl'));
        $mform->setType('container_name', PARAM_TEXT);
        $mform->addHelpButton('container_name', 'container_name', 'webgl');
        $mform->addRule('container_name', null, 'required', null, 'client');
        $ContainerName = get_config('webgl','ContainerName');
        $mform->setDefault('container_name',$ContainerName);

        $mform->addElement('advcheckbox', 'store_zip_file', get_string('store_zip_file', 'webgl'));
        $mform->addHelpButton('store_zip_file', 'store_zip_file', 'webgl');
        $mform->addRule('store_zip_file', null, 'required', null, 'client');
        $store_zip_file = get_config('webgl','store_zip_file');
        $mform->setDefault('store_zip_file',$store_zip_file);



        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }


}
