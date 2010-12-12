<?php
/* array skills contains vendor, module and version in the form:
Array
(
    [0] => Array
        (
            [Vendor] => Array (...vendor data...)
            [Module] => Array
                [0] => (
                        [0] => Array(...module data...
                                     ...array of versions...
                                )
                        )
                )
                [1] => (
                        [0] => Array(...module data...
                                     ...array of versions...
                        )
                )
        )
    [1] => Array
        (
            [Vendor] => Array (...vendor data...)
            [Module] => Array (...module data as shown above ...)
        )
)

skills that are preselected are passed in $selectedSkills
*/

if (!isset($selectedSkills)) {
    $selectedSkills = array();
}

foreach ($data as $vendorData) {
    $vendor = $vendorData ['Vendor'] ['vendorname'];
    $vendorId = $vendorData['Vendor']['id'];
    $moduleElementId = "modules$vendorId";
    $vendorOnclick = "\$('#$moduleElementId').show()";
    echo '<fieldset class="vendor" onclick="' . $vendorOnclick . '"><legend> ' . $vendor . '</legend>';
    
    foreach ($vendorData['Module'] as $moduleData) {
        if (isset($moduleData['Version'])) {
            $module = $moduleData['modulename'];
            $moduleId = $moduleData['id'];
            $versionElementId = "versions$moduleId";
            $moduleOnclick = "\$('#$versionElementId').show()";
            echo "<fieldset class=\"module\" id=\"$moduleElementId\" onclick=\"$moduleOnclick\" style=\"display:none\"><legend>$module</legend>";
            echo "<fieldset class=\"version\" id=\"$versionElementId\" style=\"display:none\">";
            
            $versionOptions = array();
            $moduleShown = false;

            foreach ($moduleData['Version'] as $versionData) {
                $version = $versionData['versionname'];
                $versionId = $versionData['id'];
                $checked = false;
                if (in_array($versionId, $selectedSkills)) {
                    $checked = true;
                    if (!$moduleShown) {
                        echo '<script type="text/javascript">';
                        echo "$(\"#$moduleElementId\").show();";
                        echo "$(\"#$versionElementId\").show();";
                        echo '</script>';
                        $moduleShown = true;
                    }
                }
                echo '<div class="skill">';
                echo $form->checkbox("Version.$versionId", array('value' => $versionId, 'hiddenField' => false, 'checked' => $checked));
                echo $version;
                echo '</div>';
            }
            //echo $form->select('skill', $versionOptions, array('multiple' => true));
                        
            echo '</fieldset>'; // version

            echo '</fieldset>'; // module
        }
    }
    echo '</fieldset>'; // vendor
}