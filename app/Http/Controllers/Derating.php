<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Derating extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
//        $validatedData = $request->validate([
//            'ambient_temp' => 'required|numeric',
//            'insulation' => 'required|numeric',
//            'gauge' => 'required|numeric', 'conductors' => 'required|numeric'
//        ]);

        $tempRating = $this->insulation($request->input('insulation'));
        $gauge = $request->input('gauge');
        $gauges = $this->gauges();
        $ampacity = $gauges[$gauge][$tempRating];
        $tempCorrectionFactor = $this->getAmbientTempCorrectionFactor($request->input('ambient_temp'), $tempRating);
        $numberConductorsCorrectionFactor = $this->getNumberConductorsCorrectionFactor($request->input('conductors'));
        $adjusted = $ampacity * $tempCorrectionFactor * $numberConductorsCorrectionFactor;
        return $adjusted;
    }

    public function gauges()
    {
        $gauges = collect(
            ['18' => array('60C' => '', '75C' => '', '90C' => '14'),
                '16' => array('60C' => '', '75C' => '', '90C' => '18'),
                '14' => array('60C' => '20', '75C' => '20', '90C' => '25'),
                '12' => array('60C' => '25', '75C' => '25', '90C' => '30'),
                '10' => array('60C' => '30', '75C' => '35', '90C' => '40'),
                '8' => array('60C' => '40', '75C' => '50', '90C' => '55'),
                '6' => array('60C' => '55', '75C' => '65', '90C' => '75'),
                '4' => array('60C' => '70', '75C' => '85', '90C' => '95'),
                '3' => array('60C' => '85', '75C' => '100', '90C' => '110'),
                '2' => array('60C' => '95', '75C' => '115', '90C' => '130'),
                '1' => array('60C' => '110', '75C' => '130', '90C' => '150'),
                '1/0' => array('60C' => '125', '75C' => '150', '90C' => '170'),
                '2/0' => array('60C' => '145', '75C' => '175', '90C' => '195'),
                '3/0' => array('60C' => '165', '75C' => '200', '90C' => '225'),
                '4/0' => array('60C' => '195', '75C' => '230', '90C' => '260'),
                '250' => array('60C' => '215', '75C' => '255', '90C' => '290'),
                '300' => array('60C' => '240', '75C' => '285', '90C' => '320'),
                '350' => array('60C' => '260', '75C' => '310', '90C' => '350'),
                '400' => array('60C' => '280', '75C' => '335', '90C' => '380'),
                '500' => array('60C' => '320', '75C' => '380', '90C' => '430'),
                '600' => array('60C' => '355', '75C' => '420', '90C' => '475'),
                '700' => array('60C' => '385', '75C' => '460', '90C' => '520'),
                '750' => array('60C' => '400', '75C' => '475', '90C' => '535'),
                '800' => array('60C' => '410', '75C' => '490', '90C' => '555'),
                '900' => array('60C' => '435', '75C' => '520', '90C' => '585'),
                '1000' => array('60C' => '455', '75C' => '545', '90C' => '615'),
                '1250' => array('60C' => '495', '75C' => '590', '90C' => '665'),
                '1500' => array('60C' => '520', '75C' => '625', '90C' => '705'),
                '1750' => array('60C' => '545', '75C' => '650', '90C' => '735'),
                '2000' => array('60C' => '560', '75C' => '665', '90C' => '750')
            ]);
        return $gauges;
    }

    /**
     * @param $insulation
     * @return mixed
     */
    public function insulation($insulation)
    {
        $types = collect(array(
            '60C' => array('TW', 'UF'),
            '75C' => array('RHW', 'THHW', 'THW', 'THWN', 'XHHW', 'USE', 'ZW'),
            '90C' => array('TBS', 'SA', 'SIS', 'FEP',
                'FEPB', 'MI', 'RHH', 'RHW-2', 'THHN', 'THHW', 'THW-2', 'THWN-2', 'USE-2', 'XHH', 'XHHW',
                'XHHW-2', 'ZW-2')));
        return $types->search(function ($item, $key) use ($insulation) {
            return in_array(strtoupper($insulation), $item);
        }) ?: 'Not Found';
    }

    public function getAmbientTempCorrectionFactor($ambientTemp, $insulationRating)
    {
        $factors = collect([
            array('max' => 25, 'min' => 21, '60C' => 1.08, '75C' => 1.05, '90C' => 1.04),
            array('max' => 30, 'min' => 26, '60C' => 1.00, '75C' => 1.00, '90C' => 1.00),
            array('max' => 35, 'min' => 31, '60C' => 0.91, '75C' => 0.94, '90C' => 0.96),
            array('max' => 40, 'min' => 36, '60C' => 0.82, '75C' => 0.88, '90C' => 0.91),
            array('max' => 45, 'min' => 41, '60C' => 0.71, '75C' => 0.82, '90C' => 0.87),
            array('max' => 50, 'min' => 46, '60C' => 0.58, '75C' => 0.75, '90C' => 0.82),
            array('max' => 55, 'min' => 51, '60C' => 0.41, '75C' => 0.67, '90C' => 0.76),
            array('max' => 60, 'min' => 56, '60C' => 0, '75C' => 0.58, '90C' => 0.71),
            array('max' => 70, 'min' => 61, '60C' => 0, '75C' => 0.33, '90C' => 0.58),
            array('max' => 80, 'min' => 71, '60C' => 0, '75C' => 0, '90C' => 0.41)
        ]);

        $range = $factors->first(function ($value, $key) use ($ambientTemp) {
            return ($ambientTemp >= $value['min'] && $ambientTemp <= $value['max']);
        });

        return $range[$insulationRating];
    }

    public function getNumberConductorsCorrectionFactor($conductors)
    {
        $factors = collect([
            array('min' => 1, 'max' => 3, 'factor' => 1),
            array('min' => 4, 'max' => 6, 'factor' => 0.8),
            array('min' => 7, 'max' => 9, 'factor' => 0.7),
            array('min' => 10, 'max' => 20, 'factor' => 0.5),
            array('min' => 21, 'max' => 30, 'factor' => 0.45),
            array('min' => 31, 'max' => 40, 'factor' => 0.4),
            array('min' => 41, 'max' => 9999, 'factor' => 0.35),
        ]);

        $range = $factors->first(function ($item, $key) use ($conductors) {
            return ($conductors >= $item['min'] && $conductors <= $item['max']);
        });

        return $range['factor'];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
