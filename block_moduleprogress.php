<?php
defined('MOODLE_INTERNAL') || die();

class block_moduleprogress extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_moduleprogress');
    }

    public function get_content() {
        global $COURSE, $DB, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $context = context_course::instance($COURSE->id);

        // 1. Nur Nutzer mit der Rolle 'student' zählen
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $student_ids = array();
        
        if ($studentrole) {
            $students = get_role_users($studentrole->id, $context, false, 'u.id');
            if (!empty($students)) {
                $student_ids = array_keys($students);
            }
        }

        $total_students = count($student_ids);
        $is_student = in_array($USER->id, $student_ids);

        // CSS für Transparenz & Overlays direkt mitgeben
        $html = '
        <style>
            .mp-overlay-container {
                position: relative;
            }
            .mp-overlay {
                position: absolute;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(255, 255, 255, 0.88);
                backdrop-filter: blur(2px);
                z-index: 10;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 15px;
                text-align: center;
                border-radius: 12px;
                box-shadow: inset 0 0 10px rgba(0,0,0,0.05);
            }
            .mp-overlay-dark {
                background: rgba(10, 25, 47, 0.90) !important;
                color: #ffffff !important;
            }
            .mp-explain-badge {
                background: #007bff;
                color: white;
                font-size: 0.8rem;
                padding: 3px 8px;
                border-radius: 4px;
                margin-bottom: 5px;
                display: inline-block;
            }
        </style>

        <div class="moduleprogress-wrapper mb-3">
            <button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="mp-toggle-btn" onclick="toggleModuleProgress()">
                <i class="fa fa-chevron-up" id="mp-toggle-icon"></i> <span id="mp-toggle-text">Modulfortschritt ausblenden</span>
            </button>
            
            <div id="moduleprogress-content-body">';

        // -------------------------------------------------------------------
        // DASHBOARD HTML (Layout für alle, Werte verblasst bei Dozenten)
        // -------------------------------------------------------------------
        
        $html .= '
        <div class="card p-3 shadow-sm border-0 mb-3" style="border-radius: 16px;">
            <div class="row position-relative">
                
                <!-- LINKER BEREICH: Fortschritt & Empfehlung -->
                <div class="col-md-8 position-relative">
                    ' . (!$is_student ? '
                    <div class="mp-overlay">
                        <div>
                            <span class="mp-explain-badge">Dozenten-Info: Prozent & Empfehlung</span>
                            <p class="small text-dark mb-0">Studierende sehen hier ihren berechneten Gesamtfortschritt (in %) sowie automatisierte Empfehlungen, welche Aufgaben zur Notenverbesserung als Nächstes absolviert werden sollten.</p>
                        </div>
                    </div>' : '') . '

                    <div class="d-flex align-items-center">
                        <div class="text-center pr-4 border-right" style="min-width: 150px;">
                            <h2 class="display-4 font-weight-bold text-dark mb-0">' . ($is_student ? '0%' : 'XX%') . '</h2>
                        </div>
                        <div class="pl-4">
                            <h4 class="font-weight-bold">Dranbleiben!</h4>
                            <p class="text-muted small mb-2">Starte mit den wichtigsten Leistungsbereichen, um deinen Wert sichtbar zu erhöhen.</p>
                            <p class="font-weight-bold text-primary mb-0">Noch 70 % bis Bronze</p>
                        </div>
                    </div>
                    <div class="bg-light p-3 mt-3 rounded">
                        <strong class="text-warning">Empfehlung:</strong>
                        <p class="small text-muted mb-0">Fokus: Basis aufbauen. Bearbeite zuerst offene Aufgaben...</p>
                    </div>
                </div>

                <!-- RECHTER BEREICH: Badges -->
                <div class="col-md-4 position-relative border-left">
                    ' . (!$is_student ? '
                    <div class="mp-overlay">
                        <div>
                            <span class="mp-explain-badge">Dozenten-Info: Badges</span>
                            <p class="small text-dark mb-0">Schaltet Meilensteine frei: Bronze (70%), Silber (80%) und Gold (90%).</p>
                        </div>
                    </div>' : '') . '

                    <h6 class="font-weight-bold mb-3">Badge-Vorschau</h6>
                    <div class="d-flex justify-content-between text-center">
                        <div class="p-2 border rounded"><small>Bronze<br>ab 70%</small></div>
                        <div class="p-2 border rounded"><small>Silber<br>ab 80%</small></div>
                        <div class="p-2 border rounded"><small>Gold<br>ab 90%</small></div>
                    </div>
                </div>

            </div>
        </div>

        <!-- UNTERER BEREICH: Ranking -->
        <div class="card p-4 text-white position-relative" style="background-color: #0b132b; border-radius: 16px;">
            ' . (!$is_student ? '
            <div class="mp-overlay mp-overlay-dark">
                <div style="max-width: 600px;">
                    <span class="mp-explain-badge bg-info">Dozenten-Info: Kohorten-Ranking</span>
                    <p class="small mb-0">Zeigt das anonymisierte Ranking. In der Ecke oben rechts werden <b>ausschließlich Teilnehmende (aktuell ' . $total_students . ')</b> gezählt. Lehrende/Admins verfälschen diese Zahl nicht mehr.</p>
                </div>
            </div>' : '') . '

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0 text-white">Anonymisiertes Kohorten-Ranking</h5>
                    <small class="text-muted">Deine Position ist sichtbar — andere Teilnehmende bleiben anonym.</small>
                </div>
                <span class="badge badge-warning p-2" style="font-size: 1rem;">#' . ($is_student ? '18' : 'X') . ' von ' . $total_students . '</span>
            </div>

            <div class="bg-dark p-2 rounded mb-1 d-flex justify-content-between"><span>#1 Teilnehmer 1</span><span>95%</span></div>
            <div class="bg-dark p-2 rounded mb-1 d-flex justify-content-between"><span>#2 Teilnehmer 2</span><span>66.7%</span></div>
            <div class="text-center my-2">...</div>
            <div class="bg-warning text-dark p-2 rounded font-weight-bold d-flex justify-content-between"><span>#18 Du</span><span>0%</span></div>
        </div>';

        $html .= '
            </div>
        </div>

        <!-- Robuster Inline-Script für den Ausklapp-Button -->
        <script>
        function toggleModuleProgress() {
            var body = document.getElementById("moduleprogress-content-body");
            var icon = document.getElementById("mp-toggle-icon");
            var text = document.getElementById("mp-toggle-text");
            
            if (body.style.display === "none") {
                body.style.display = "block";
                text.innerText = "Modulfortschritt ausblenden";
                icon.className = "fa fa-chevron-up";
            } else {
                body.style.display = "none";
                text.innerText = "Modulfortschritt einblenden";
                icon.className = "fa fa-chevron-down";
            }
        }
        </script>';

        $this->content->text = $html;
        return $this->content;
    }
}
