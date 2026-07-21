<?php
defined('MOODLE_INTERNAL') || die();

class block_moduleprogress extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_moduleprogress');
    }

    public function get_content() {
        global $COURSE, $DB, $USER, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $context = context_course::instance($COURSE->id);

        // 1. Rollen-Filter: Nur Nutzer mit der Rolle 'student' (Teilnehmer) ermitteln
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $student_ids = array();
        
        if ($studentrole) {
            $students = get_role_users($studentrole->id, $context, false, 'u.id, u.firstname, u.lastname');
            $student_ids = array_keys($students);
        }

        $total_students = count($student_ids);
        $is_student = in_array($USER->id, $student_ids);

        // HTML-Start mit Toggle-Button
        $html = '
        <div class="moduleprogress-wrapper">
            <button type="button" class="btn btn-sm btn-outline-secondary mb-3 toggle-moduleprogress-btn" id="toggle-block-btn">
                <i class="fa fa-chevron-up toggle-icon"></i> <span class="toggle-text">Modulfortschritt ausblenden</span>
            </button>
            <div id="moduleprogress-content-body" class="moduleprogress-content">';

        // 2. Weiche: Dozenten/Admins vs. Teilnehmer
        if (!$is_student) {
            // --- ANSICHT FÜR NICHT-TEILNEHMER (Dozierende / Admins) ---
            $html .= '
            <div class="card p-4 shadow-sm border-0" style="background-color: #f8f9fa;">
                <h4 class="mb-3"><i class="fa fa-info-circle text-primary"></i> Übersicht zum Modulfortschritt (Dozenten-Ansicht)</h4>
                <p>Sie sehen diese Erklärung, da Sie nicht als Teilnehmer in diesem Kurs gewertet werden und somit keine eigenen Punkte sammeln.</p>
                <hr>
                <div class="row text-dark">
                    <div class="col-md-4 mb-3">
                        <h6><i class="fa fa-chart-line text-success"></i> 1. Prozentanzeige & Badges</h6>
                        <p class="small text-muted mb-0">Zeigt den aktuellen Gesamtfortschritt der Studierenden an (basierend auf bearbeiteten Aufgaben & LSKs). Bei 70 % (Bronze), 80 % (Silber) und 90 % (Gold) schalten sich Badges frei.</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h6><i class="fa fa-lightbulb text-warning"></i> 2. Empfehlungs-Box</h6>
                        <p class="small text-muted mb-0">Gibt den Studierenden automatische Hinweise, welche LSKs oder Aufgaben sie wiederholen können, um den eigenen Prozentwert am schnellsten zu steigern.</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h6><i class="fa fa-trophy text-info"></i> 3. Kohorten-Ranking</h6>
                        <p class="small text-muted mb-0">Listet <b>ausschließlich Teilnehmer</b> anonym auf. Studierende sehen nur die Top 5 und die eigene Position (z. B. "#18 von ' . $total_students . '").</p>
                    </div>
                </div>
            </div>';
        } else {
            // --- ANSICHT FÜR TEILNEHMER ---
            // (Hier deine bisherige Berechnungs-Logik einfügen)
            
            // Beispiel für die korrigierte Platzanzeige im HTML:
            // $my_rank_badge = "#" . $my_rank . " von " . $total_students;
            
            // FÜGE HIER DEINEN BISHERIGEN DASHBOARD-HTML-CODE EIN
            // ...
        }

        // HTML-Ende
        $html .= '
            </div>
        </div>';

        // JavaScript initialisieren
        $PAGE->requires->js_call_amd('block_moduleprogress/gauge', 'initToggle');

        $this->content->text = $html;
        return $this->content;
    }
}
