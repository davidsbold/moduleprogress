<?php
defined('MOODLE_INTERNAL') || die();

class block_moduleprogress_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        $mform->addElement('header', 'configheader', 'Leaderboard-Einstellungen');

        $mform->addElement('advcheckbox', 'config_showleaderboard', 'Anonymisiertes Ranking anzeigen');
        $mform->setDefault('config_showleaderboard', 1);

        $mform->addElement('text', 'config_rankingcourses', 'Kursübergreifende Kurs-IDs');
        $mform->setType('config_rankingcourses', PARAM_TEXT);
        $mform->addHelpButton('config_rankingcourses', 'rankingcourses', 'block_moduleprogress');

        $mform->addElement('select', 'config_maxrows', 'Anzahl sichtbarer Ranking-Zeilen', [
            3 => '3',
            5 => '5',
            10 => '10'
        ]);
        $mform->setDefault('config_maxrows', 5);
    }
}