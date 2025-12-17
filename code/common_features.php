<?php

/* 
 * Community sourced library of default feature settings
 */


/*
 * The feature code is not currently used but this data is collecting it to future proof it.
 * Long term it may be needed to address a potential feature name collission 
 * between vendors to seperate features with identical name .
 * 
 * 
 * /opt/lmtools/lmutil lmstat -a -c 50000@lic.server.com
 * 
 * Vendor daemon status (on lic.server.com):
 *
 *   FEATURECODE: UP v11.16.5
 * Feature usage info:
 * 
 * Search for features in use with
 * SELECT servers.name, servers.id, features.name , max( num_users ) FROM `usage` 
 *	LEFT JOIN licenses ON `usage`.license_id = licenses.id 
 *	LEFT JOIN servers ON licenses.server_id = servers.id
 *	LEFT JOIN features ON licenses.feature_id = features.id
 * WHERE num_users > 0 AND servers.id = 9
 * GROUP BY servers.name, servers.id, features.name  ORDER BY servers.name, features.name;
 */


$default_feature_settings = [
    //Software: Abaqus      Feature Code: ABAQUSLM
    "ABAQUSLM" => [
        "abaqus_extended" => [ "show" => false, "track"=>false ],
    ],

    //Software: Geneious      Feature Code: geneious
    "geneious" => [
        "floating_license" => [ "show" => true, "track"=>true ],
    ],
    
    //Software: MatLab      Feature Code: MLM  
    "MLM"=>[
	"Aerospace_Blockset" => [ "show" => false, "track"=>true ],
	"Aerospace_Toolbox" => [ "show" => false, "track"=>true ],
	"Antenna_Toolbox" => [ "show" => false, "track"=>true ],
	"Audio_System_Toolbox" => [ "show" => false, "track"=>true ],
	"Automated_Driving_Toolbox" => [ "show" => false, "track"=>true ],
	"AUTOSAR_Blockset" => [ "show" => false, "track"=>true ],
	"Bioinformatics_Toolbox" => [ "show" => false, "track"=>true ],
	"Bluetooth_Toolbox" => [ "show" => false, "track"=>true ],
	"C2000_Blockset" => [ "show" => false, "track"=>true ],
	"Communication_Toolbox" => [ "show" => false, "track"=>true ],
	"Compiler" => [ "show" => false, "track"=>true ],
	"Control_Toolbox" => [ "show" => false, "track"=>true ],
	"Curve_Fitting_Toolbox" => [ "show" => true, "track"=>true ],
	"Data_Acq_Toolbox" => [ "show" => false, "track"=>true ],
	"Database_Toolbox" => [ "show" => false, "track"=>true ],
	"Datafeed_Toolbox" => [ "show" => false, "track"=>true ],
	"DDS_Blockset" => [ "show" => false, "track"=>true ],
	"Deep_Learning_HDL_Toolbox" => [ "show" => false, "track"=>true ],
	"Distrib_Computing_Toolbox" => [ "show" => false, "track"=>true ],
	"DSP_HDL_Toolbox" => [ "show" => false, "track"=>true ],
	"Econometrics_Toolbox" => [ "show" => false, "track"=>true ],
	"EDA_Simulator_Link" => [ "show" => false, "track"=>true ],
	"Excel_Link" => [ "show" => false, "track"=>true ],
	"Filter_Design_HDL_Coder" => [ "show" => false, "track"=>true ],
	"Fin_Instruments_Toolbox" => [ "show" => false, "track"=>true ],
	"Financial_Toolbox" => [ "show" => false, "track"=>true ],
	"Fixed_Point_Toolbox" => [ "show" => false, "track"=>true ],
	"Fuzzy_Toolbox" => [ "show" => false, "track"=>true ],
	"GADS_Toolbox" => [ "show" => false, "track"=>true ],
	"GPU_Coder" => [ "show" => false, "track"=>true ],
	"Identification_Toolbox" => [ "show" => false, "track"=>true ],
	"Image_Acquisition_Toolbox" => [ "show" => false, "track"=>true ],
	"Image_Toolbox" => [ "show" => true, "track"=>true ],
	"Instr_Control_Toolbox" => [ "show" => false, "track"=>true ],
	"Lidar_Toolbox" => [ "show" => false, "track"=>true ],
	"LTE_HDL_Toolbox" => [ "show" => false, "track"=>true ],
	"LTE_Toolbox" => [ "show" => false, "track"=>true ],
	"MAP_Toolbox" => [ "show" => false, "track"=>true ],
	"MATLAB" => [ "show" => true, "track"=>true ],
	"MATLAB_5G_Toolbox" => [ "show" => false, "track"=>true ],
	"MATLAB_Builder_for_Java" => [ "show" => false, "track"=>true ],
	"MATLAB_Coder" => [ "show" => false, "track"=>true ],
	"MATLAB_Report_Gen" => [ "show" => false, "track"=>true ],
	"MATLAB_Test" => [ "show" => false, "track"=>true ],
	"MBC_Toolbox" => [ "show" => false, "track"=>true ],
	"Medical_Imaging_Toolbox" => [ "show" => false, "track"=>true ],
	"Mixed_Signal_Blockset" => [ "show" => false, "track"=>true ],
	"Motor_Control_Blockset" => [ "show" => false, "track"=>true ],
	"MPC_Toolbox" => [ "show" => false, "track"=>true ],
	"Navigation_Toolbox" => [ "show" => false, "track"=>true ],
	"Neural_Network_Toolbox" => [ "show" => false, "track"=>true ],
	"OPC_Toolbox" => [ "show" => false, "track"=>true ],
	"Optimization_Toolbox" => [ "show" => false, "track"=>true ],
	"PDE_Toolbox" => [ "show" => false, "track"=>true ],
	"Phased_Array_System_Toolbox" => [ "show" => false, "track"=>true ],
	"PolySpace_Bug_Finder" => [ "show" => false, "track"=>true ],
	"PolySpace_Bug_Finder_Engine" => [ "show" => false, "track"=>true ],
	"PolySpace_Server_C_CPP" => [ "show" => false, "track"=>true ],
	"Power_System_Blocks" => [ "show" => false, "track"=>true ],
	"Powertrain_Blockset" => [ "show" => false, "track"=>true ],
	"Pred_Maintenance_Toolbox" => [ "show" => false, "track"=>true ],
	"Radar_Toolbox" => [ "show" => false, "track"=>true ],
	"Real-Time_Win_Target" => [ "show" => false, "track"=>true ],
	"Real-Time_Workshop" => [ "show" => false, "track"=>true ],
	"Reinforcement_Learn_Toolbox" => [ "show" => false, "track"=>true ],
	"RF_Blockset" => [ "show" => false, "track"=>true ],
	"RF_PCB_Toolbox" => [ "show" => false, "track"=>true ],
	"RF_Toolbox" => [ "show" => false, "track"=>true ],
	"Risk_Management_Toolbox" => [ "show" => false, "track"=>true ],
	"Robotics_System_Toolbox" => [ "show" => false, "track"=>true ],
	"Robust_Toolbox" => [ "show" => false, "track"=>true ],
	"ROS_Toolbox" => [ "show" => false, "track"=>true ],
	"RTW_Embedded_Coder" => [ "show" => false, "track"=>true ],
	"Satellite_Comm_Toolbox" => [ "show" => false, "track"=>true ],
	"Sensor_Fusion_and_Tracking" => [ "show" => false, "track"=>true ],
	"SerDes_Toolbox" => [ "show" => false, "track"=>true ],
	"Signal_Blocks" => [ "show" => false, "track"=>true ],
	"Signal_Integrity_Toolbox" => [ "show" => false, "track"=>true ],
	"Signal_Toolbox" => [ "show" => false, "track"=>true ],
	"SimBiology" => [ "show" => false, "track"=>true ],
	"SimDriveline" => [ "show" => false, "track"=>true ],
	"SimEvents" => [ "show" => false, "track"=>true ],
	"SimHydraulics" => [ "show" => false, "track"=>true ],
	"SimMechanics" => [ "show" => false, "track"=>true ],
	"Simscape" => [ "show" => false, "track"=>true ],
	"Simscape_Battery" => [ "show" => false, "track"=>true ],
	"SIMULINK" => [ "show" => false, "track"=>true ],
	"Simulink_Code_Inspector" => [ "show" => false, "track"=>true ],
	"Simulink_Compiler" => [ "show" => false, "track"=>true ],
	"Simulink_Control_Design" => [ "show" => false, "track"=>true ],
	"Simulink_Coverage" => [ "show" => false, "track"=>true ],
	"Simulink_Design_Optim" => [ "show" => false, "track"=>true ],
	"Simulink_Design_Verifier" => [ "show" => false, "track"=>true ],
	"Simulink_Fault_Analyzer" => [ "show" => false, "track"=>true ],
	"Simulink_HDL_Coder" => [ "show" => false, "track"=>true ],
	"Simulink_PLC_Coder" => [ "show" => false, "track"=>true ],
	"SIMULINK_Report_Gen" => [ "show" => false, "track"=>true ],
	"Simulink_Requirements" => [ "show" => false, "track"=>true ],
	"Simulink_Test" => [ "show" => false, "track"=>true ],
	"SL_Verification_Validation" => [ "show" => false, "track"=>true ],
	"SoC_Blockset" => [ "show" => false, "track"=>true ],
	"Stateflow" => [ "show" => false, "track"=>true ],
	"Statistics_Toolbox" => [ "show" => true, "track"=>true ],
	"Symbolic_Toolbox" => [ "show" => false, "track"=>true ],
	"System_Composer" => [ "show" => false, "track"=>true ],
	"Text_Analytics_Toolbox" => [ "show" => false, "track"=>true ],
	"UAV_Toolbox" => [ "show" => false, "track"=>true ],
	"Vehicle_Dynamics_Blockset" => [ "show" => false, "track"=>true ],
	"Vehicle_Network_Toolbox" => [ "show" => false, "track"=>true ],
	"Video_and_Image_Blockset" => [ "show" => false, "track"=>true ],
	"Virtual_Reality_Toolbox" => [ "show" => false, "track"=>true ],
	"Vision_HDL_Toolbox" => [ "show" => false, "track"=>true ],
	"Wavelet_Toolbox" => [ "show" => false, "track"=>true ],
	"Wireless_Testbench" => [ "show" => false, "track"=>true ],
	"WLAN_System_Toolbox" => [ "show" => false, "track"=>true ],
	"XPC_Target" => [ "show" => false, "track"=>true ],
    ],    
    
];


//Do not edit below this line

$default_feature_settings_temp = [];
foreach( $default_feature_settings as $vendor ){
    foreach( array_keys($vendor) as $feature ){
        $default_feature_settings_temp[ $feature ] = $vendor[$feature];
    }
}


define('DEFAULT_FEATURE_SETTINGS', $default_feature_settings_temp);
unset($default_feature_settings);