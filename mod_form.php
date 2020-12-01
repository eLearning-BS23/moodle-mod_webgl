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
        global $CFG;
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

        $mform->addElement('filepicker', 'importfile', get_string('input:file', 'webgl'),null, ['accepted_types' => '.zip']);
        $mform->addHelpButton('importfile', 'ziparchive', 'webgl');
        $mform->addRule('importfile', null, 'required');

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



        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }
}
