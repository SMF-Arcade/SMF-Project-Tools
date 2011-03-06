<?php

remove_integration_function('integrate_pre_include', '$sourcedir/ProjectTools/Hooks.php');
remove_integration_function('integrate_pre_load', 'ProjectTools_Hooks::pre_load');
remove_integration_function('integrate_actions', 'ProjectTools_Hooks::actions');
remove_integration_function('integrate_admin_areas', 'ProjectTools_Hooks::admin_areas');
remove_integration_function('integrate_core_features', 'ProjectTools_Hooks::core_features');
