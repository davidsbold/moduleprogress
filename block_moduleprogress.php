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

        // 1. ZÄHLER: Nur Nutzer mit der Rolle 'student' abfragen
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $student_ids = array();
        
        if ($studentrole) {
            $students = get_role_users($studentrole->id, $context, false, 'u.id');
            if (!empty($students)) {
                $student_ids = array_keys($students);
            }
        }

        // Richtige Gesamtanzahl NUR der Teilnehmer (z. B. 31)
        $total_students = count($student_ids);
        $is_student = in_array($USER->id, $student_ids);

        // --- HIER STARTET DEIN REGULÄRER ORIGINAL-HTML-CODE ---
        // (Setze hier Variablen wie $my_rank, $my_percentage etc. für dein Template ein)
        
        // CSS für die Dozenten-Overlay-Schicht
        $overlay_css = '
        <style>
            .mp-explain-wrapper { position: relative; }
            .mp-teacher-overlay {
                position: absolute;
                top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(255, 255, 255, 0.88);
                backdrop-filter: blur(2px);
                z-index: 99;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                text-align: center;
                border-radius: 12px;
            }
            .mp-teacher-overlay-dark {
                background: rgba(4, 9, 26, 0.88) !important;
                color: #ffffff !important;
            }
            .mp-badge-info {
                background: #f1c40f;
                color: #000;
                font-weight: bold;
                padding: 4px 10px;
                border-radius: 6px;
                display: inline-block;
                margin-bottom: 8px;
            }
        </style>';

        $html = $overlay_css;

        // Toggle Button
        $html .= '
        <div class="moduleprogress-outer-container mb-3">
            <button type="button" class="btn btn-sm btn-outline-secondary mb-2" id="mp-toggle-btn" onclick="toggleModuleProgressBody()">
                <i class="fa fa-chevron-up" id="mp-toggle-icon"></i> <span id="mp-toggle-text">Modulfortschritt ausblenden</span>
            </button>
            <div id="moduleprogress-content-body">';

        // ----------------------------------------------------------------------
        // DEIN ORIGINALES UI LAYOUT (UNVERÄNDERT)
        // ----------------------------------------------------------------------

        // A) OBERER KASTEN (Fortschritt, Empfehlung, Badges)
        $html .= '<div class="mp-explain-wrapper">';
        
        if (!$is_student) {
            $html .= '
            <div class="mp-teacher-overlay">
                <div>
                    <span class="mp-badge-info">Dozenten-Info: Modulfortschritt & Badges</span>
                    <p class="mb-0 text-dark" style="max-width: 600px;">
                        Hier sehen Studierende ihren individuellen Fortschritt in %, personalisierte Empfehlungen zur Notenverbesserung sowie freigeschaltete Badges (Bronze ab 70%, Silber ab 80%, Gold ab 90%).
                    </p>
                </div>
            </div>';
        }

        /* 
         * >>> FÜGE HIER DEINEN DEKLARIERTEN ORIGINAL-HTML-CODE FÜR DEN OBEREN KASTEN EIN <<<
         * Beispiel: $html .= $dein_originaler_oberer_kasten_html;
        */

        $html .= '</div>'; // Ende Oberer Kasten Wrapper

        // B) UNTERER KASTEN (Anonymisiertes Kohorten-Ranking)
        $html .= '<div class="mp-explain-wrapper mt-3">';

        if (!$is_student) {
            $html .= '
            <div class="mp-teacher-overlay mp-teacher-overlay-dark">
                <div>
                    <span class="mp-badge-info">Dozenten-Info: Kohorten-Ranking</span>
                    <p class="mb-0 text-white" style="max-width: 600px;">
                        Anonymisierte Rangliste aller Studierenden. Oben rechts werden <b>ausschließlich Teilnehmende (' . $total_students . ')</b> gezählt. Lehrende und Admins werden im Ranking nicht geführt.
                    </p>
                </div>
            </div>';
        }

        /* 
         * >>> FÜGE HIER DEINEN DEKLARIERTEN ORIGINAL-HTML-CODE FÜR DAS RANKING EIN <<<
         * WICHTIG: Ersetze in deiner Badge oben rechts die Zahl der Nutzer durch $total_students
         * (z.B. "#" . $my_rank . " von " . $total_students)
        */

        $html .= '</div>'; // Ende Ranking Wrapper

        // Container zu machen
        $html .= '
            </div>
        </div>';

        // Toggle-Skript
        $html .= '
        <script>
        function toggleModuleProgressBody() {
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
