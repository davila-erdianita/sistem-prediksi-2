<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_topsis extends CI_Model {
	public function hitung(){
		$this->db->select('*');
        $this->db->from('tbl_kriteria');
        $query = $this->db->get();
        $data_kriteria = $query->result_array();

        //metode ROC untuk pembobotan
        $jml_kriteria = count($data_kriteria);
        //var_dump($jml_kriteria);
        $bobot = array();
        $kriteria = array();
        $alternatif = array();
        $tabel_hasil = array();

        for ($i=0; $i < $jml_kriteria; $i++) { 
            $jumlah=0;
            for ($j=$jml_kriteria; $j > $i; $j--) { 
                $jumlah += 1/$j;
            }
            //$hasil = 
            $bobot[$i] = $jumlah/$jml_kriteria;
            $kriteria[$i] = $data_kriteria[$i]['kode_kriteria'];
            
        }
        $tabel_hasil['bobot'] = $bobot;
        $tabel_hasil['kriteria'] = $kriteria;
        // echo "<pre>";
        // print_r($tabel_hasil);
        // echo "</pre>";

        //metode TOPSIS
        $this->db->select('DISTINCT(tbl_hasil_penilaian.kode_alternatif),tbl_alternatif.keterangan');
        $this->db->from('tbl_alternatif');
        $this->db->join('tbl_hasil_penilaian','tbl_hasil_penilaian.kode_alternatif = tbl_alternatif.kode_alternatif','inner');
        $this->db->order_by('length(tbl_hasil_penilaian.kode_kriteria),tbl_hasil_penilaian.kode_kriteria','ASC');
        $this->db->order_by('length(tbl_hasil_penilaian.kode_alternatif),tbl_hasil_penilaian.kode_alternatif','ASC');
        $query = $this->db->get();
        $data_alternatif = $query->result_array();

        $jml_alternatif = count($data_alternatif);

        // foreach ($data_alternatif as $key => $value) {
        //     $alternatif[$key] = $value; 
        // }
        
        // $tabel_hasil['alternatif'] = $alternatif;

        $this->db->select('*');
        $this->db->from('tbl_hasil_penilaian');
        $this->db->order_by('length(kode_kriteria),kode_kriteria','ASC');
        $this->db->order_by('length(kode_alternatif),kode_alternatif','ASC');
        // $this->db->order_by('length(kode_alternatif),kode_alternatif','ASC');
        // $this->db->order_by('kode_kriteria','ASC');
        $query = $this->db->get();
        $data_penilaian = $query->result_array();

        // echo "<pre>";
        // print_r($data_penilaian);
        // echo "</pre>";

        $jml_data = count($data_penilaian);
        
        
        $X = array();
        $matriks_normalisasi = array();
        $matriks_bobot = array();
        $positif_ideal = array();
        $negatif_ideal = array();
        $D_positif = array();
        $D_negatif = array();
        $hasil = array();
        $urutan= array();
        $i = 0;
        $j = 0;
        while($i < $jml_kriteria){
            $k = 0;$jumlah = 0;
            $kode_kriteria = 0;
            $kode_kriteria = $data_kriteria[$i]['kode_kriteria'];
            //MENGHITUNG NILAI |X1| SAMPAI |X5|
            while(($j < $jml_data)&&($k != $jml_alternatif)){
                if($kode_kriteria == $data_penilaian[$j]['kode_kriteria']){
                        $jumlah +=  pow($data_penilaian[$j]['nilai'],2);
                        // echo "<pre>";
                        // print_r($jumlah);
                        // echo "</pre>";
                    $k++;
                }
                $j++;
            }

            //MENGHITUNG MATRIKS KEPUTUSAN NORMALISASI
            $X[$i] = sqrt($jumlah);
            $k=0;
            $j=0;
            while(($j < $jml_data)&&($k != $jml_alternatif)){
                if($kode_kriteria == $data_penilaian[$j]['kode_kriteria']){
                        $matriks_normalisasi[$k][$i] =  $data_penilaian[$j]['nilai']/$X[$i]; 
                    $k++;
                }
                $j++;
            }

            //MATRIKS KEPUTUSAN TERNORMALISASI TERBOBOT(KARENA DIKALI BOBOT)
            $k=0;
            $j=0;
            while(($j < $jml_data)&&($k != $jml_alternatif)){
                if($kode_kriteria == $data_penilaian[$j]['kode_kriteria']){
                        $matriks_bobot[$k][$i] =  $bobot[$i]*$matriks_normalisasi[$k][$i]; 
                    $k++;
                }
                $j++;
            }

            
            

            $i++;
        }

        $tabel_hasil['matriks_normalisasi'] = $matriks_normalisasi;
        $tabel_hasil['matriks_bobot'] = $matriks_bobot;

        // echo "<pre>";
        // print_r($matriks_normalisasi);
        // echo "</pre>";
        // echo "<pre>";
        // print_r($matriks_bobot);
        // echo "</pre>";

        $this->db->select('tbl_kriteria.id_jenis,tbl_jenis.id_jenis,tbl_jenis.nama_jenis');
        $this->db->from('tbl_kriteria');
        // $this->db->join('tbl_hasil_penilaian','tbl_hasil_penilaian.kode_kriteria = tbl_kriteria.kode_kriteria','inner');
        $this->db->join('tbl_jenis','tbl_jenis.id_jenis = tbl_kriteria.id_jenis','inner');
        $this->db->order_by('length(kode_kriteria),kode_kriteria','ASC');
        $query = $this->db->get();
        $jenis_cek = $query->result_array();

            //Y+(solusi ideal positif) & y-(solusi ideal negatif)
            $j = 0;
             while($j<$jml_kriteria){
                $y = array_column($matriks_bobot,$j);
                if($jenis_cek[$j]['nama_jenis'] == 'benefit'){
                    $negatif_ideal[$j] = min($y);
                    $positif_ideal[$j] = max($y);
                }else{
                    $negatif_ideal[$j] = max($y);
                    $positif_ideal[$j] = min($y);
                }
                $j++;
             }
        
                // echo "<pre>"; 
                // print_r($negatif_ideal);
                // echo "</pre>";  
                // echo "++++++++++++++++++++<br>";
                // echo "<pre>";
                // print_r($positif_ideal);
                // echo "</pre>"; 


                $tabel_hasil['positif_ideal'] = $positif_ideal;
                $tabel_hasil['negatif_ideal'] = $negatif_ideal;


                //MENGHITUNG NILAI D+
                $i = 0;
                 while($i<$jml_alternatif){
                    $j = 0;
                    $jumlah_positif = 0;
                    $jumlah_negatif = 0; 
                    while($j<$jml_kriteria){
                        $jumlah_positif += pow(($matriks_bobot[$i][$j]-$positif_ideal[$j]),2);
                        $jumlah_negatif += pow(($matriks_bobot[$i][$j]-$negatif_ideal[$j]),2);
                        $j++;
                    }
                    $D_positif[$i] = sqrt($jumlah_positif);
                    $D_negatif[$i] = sqrt($jumlah_negatif);
                    $i++;
                 }

                $tabel_hasil['D_positif'] = $D_positif;
                $tabel_hasil['D_negatif'] = $D_negatif;

                 //menghitung nilai referensi
                 $i = 0;
                 while($i<$jml_alternatif){
                    $hasil[$i]['kode_alternatif'] = $data_alternatif[$i]['kode_alternatif'];
                    $hasil[$i]['keterangan'] = $data_alternatif[$i]['keterangan'];
                    $hasil[$i]['nilai_topsis'] =  $D_negatif[$i]/($D_positif[$i]+$D_negatif[$i]);
                    // $urutan[$i]['ranking'] =  $i+1;
                    // $urutan[$i]['nilai_topsis'] = $hasil[$i]['nilai_topsis'];
                    $i++;
                 }

// echo "<pre>";
//                 print_r($hasil);
//                 echo "</pre>"; 
                 $tabel_hasil['hasil'] = $hasil;

                 //pengurutan dengan insetion sort
                 for($i=0;$i<$jml_alternatif;$i++){
                    for($j=$i-1;$j>=0;$j--){
                        if($hasil[$j]['nilai_topsis'] < $hasil[$j+1]['nilai_topsis'] ){
                         
                            // $temp = $urutan[$j]['nilai_topsis'];
                            // $urutan[$j]['nilai_topsis'] = $urutan[$j+1]['nilai_topsis'];
                            // $urutan[$j+1]['nilai_topsis'] = $temp;


                            // $temp = $urutan[$j]['ranking'];
                            // $urutan[$j]['ranking'] = $urutan[$j+1]['ranking'];
                            // $urutan[$j+1]['ranking'] = $temp;
                            $temp = $hasil[$j]['nilai_topsis'];
                            $hasil[$j]['nilai_topsis'] = $hasil[$j+1]['nilai_topsis'];
                            $hasil[$j+1]['nilai_topsis'] = $temp;


                            $temp = $hasil[$j]['keterangan'];
                            $hasil[$j]['keterangan'] = $hasil[$j+1]['keterangan'];
                            $hasil[$j+1]['keterangan'] = $temp;

                            $temp = $hasil[$j]['kode_alternatif'];
                            $hasil[$j]['kode_alternatif'] = $hasil[$j+1]['kode_alternatif'];
                            $hasil[$j+1]['kode_alternatif'] = $temp;

                        }
                         
                    }


                 }

                 $tabel_hasil['pengurutan'] = $hasil;

                 //array_multisort($hasil, ['nilai_topsis' => SORT_ASC]);
                // aasort($hasil,"nilai_topsis",SORT_ASC);

                // echo "<pre>";
                // print_r($hasil);
                // echo "</pre>"; 


                // echo "<pre>";
                // print_r($urutan);
                // echo "</pre>";
//$min_array = $array[array_search(min($y), $y)];
//$max_array = $array[array_search(max($y), $y)];
                 $tabel_hasil['judul'] = 'Data Perhitungan'; 
                 return $tabel_hasil;

	}
}
