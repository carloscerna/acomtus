<?php
//require('fpdf.php');

class PDF extends FPDF
{

// cabecepara de la bolata de captura de datos PARVULARIA.
function encabezado_parvularia_2()
{
   $w2=array(27,34); //determina el ancho de las columnas
   $this->ln();
    $this->SetFont('Arial','',9); // I : Italica; U: Normal;
///////////////////CREAR LA CABECERA DE LA TABLA.
// PRIMERA PARTE DEL RECTANGULO.
    $this->Rect(5,$w2[0],336,13);
    $this->setxy(5,40);
// SEGUNDA PARTE DEL CUADRO. NUMERO CORRELATIVO.
    $this->Rect(5,$w2[0],5,13);
    $this->RotatedText(6,35,'N°',0);
// TERCERA PARTE DEL CUADRO. NMERO DE IDENTIFICACION ESTUDIANTIL. NIE.
    $this->cMargin=1;
    $this->Rect(10,$w2[0],21,13);
    $this->SetXY(9.5,28);
    $this->MultiCell(22,3,'Número de Identificación del Estudiantil (NIE)',7,'C',0);
// CUARTA PARTE DEL CUADRO. NUMERO CORRELATIVO.
    $this->Rect(31,$w2[0],60,13);
    $this->RotatedText(44,35,'Nombre del estudiante',0);
// QUINTA PARTE DEL CUADRO. NUMERO CORRELATIVO.
    $this->Rect(91,$w2[0],60,13);
    $this->RotatedText(105,35,'Apellidos del estudiante',0);
// SEXTA PARTE DEL CUADRO. NMERO DE IDENTIFICACION ESTUDIANTIL. NIE.
    $this->cMargin=1;
    $this->Rect(151,$w2[0],27,13);
    $this->SetXY(153,28);
    $this->MultiCell(22,3,'Fecha de Nacimiento',7,'C',0);
// SEXTA 1 PARTE DEL CUADRO. NUMERO CORRELATIVO.
    $this->Rect(151,$w2[1],9,6);
    $this->RotatedText(153,38,'dd',0);    
// SEXTA 2 PARTE DEL CUADRO. NUMERO CORRELATIVO.
    $this->Rect(160,$w2[1],9,6);
    $this->RotatedText(161,38,'mm',0);
// SEXTA 3 PARTE DEL CUADRO. NUMERO CORRELATIVO.
    $this->Rect(169,$w2[1],9,6);
    $this->RotatedText(170,38,'aaaa',0);
// SEPTIMA 1 PARTE DEL CUADRO. SEXO.
    $this->Rect(178,$w2[1],9,6);
    $this->RotatedText(180,38,'M',0);    
// SEPTIMA 2 PARTE DEL CUADRO. SEXO.
    $this->Rect(187,$w2[1],9,6);
    $this->RotatedText(190,38,'F',0);
// OCTAVA PARTE DEL CUADRO. NMERO DE IDENTIFICACION ESTUDIANTIL. NIE.
    $this->cMargin=1;
    $this->Rect(178,$w2[0],18,13);
    $this->SetXY(175,28);
    $this->MultiCell(22,3,'Sexo',7,'C',0);
// NOVENA  PARTE DEL CUADRO. GRADO.
    $this->Rect(196,$w2[0],12,13);
    $this->RotatedText(197,35,'Grado',0);
// NOVENA PARTE DEL CUADRO. NOMBRE DEL RESPONSABLE.
    $this->Rect(208,$w2[0],93,13);
    $this->RotatedText(230,35,'Padre, madre o responsable',0);
// DECIMA PARTE DEL CUADRO. PARTIDA DE NACIMIENTO
    $this->cMargin=1;
    $this->Rect(301,$w2[0],40,13);
    $this->SetXY(304,28);
    $this->MultiCell(35,3,'Partida de Nacimiento',7,'C',0);    
// DECIMA 1 PARTE DEL CUADRO. PARTIDA DE NACIMIENTO.
    $this->Rect(301,$w2[1],20,6);
    $this->RotatedText(310,38,'Si',0);
// DECIMA 2 PARTE DEL CUADRO. PARTIDA DE NACIMIENTO.
    $this->Rect(321,$w2[1],20,6);
    $this->RotatedText(330,38,'No',0);
    $this->SetFont('Arial','',10); // I : Italica; U: Normal;
}

function encabezado_parvularia()
{
   $w2=array(57,64); //determina el ancho de las columnas
   $this->ln();
    $this->SetFont('Arial','',9); // I : Italica; U: Normal;
///////////////////CREAR LA CABECERA DE LA TABLA.
// PRIMERA PARTE DEL RECTANGULO.
    $this->Rect(5,$w2[0],336,13);
    $this->setxy(5,70);
// SEGUNDA PARTE DEL CUADRO. NUMERO CORRELATIVO.
    $this->Rect(5,$w2[0],5,13);
    $this->RotatedText(6,65,'N°',0);
// TERCERA PARTE DEL CUADRO. NMERO DE IDENTIFICACION ESTUDIANTIL. NIE.
    $this->cMargin=1;
    $this->Rect(10,$w2[0],21,13);
    $this->SetXY(9.5,58);
    $this->MultiCell(22,3,'Número de Identificación del Estudiantil (NIE)',7,'C',0);
// CUARTA PARTE DEL CUADRO. NUMERO CORRELATIVO.
    $this->Rect(31,$w2[0],60,13);
    $this->RotatedText(44,65,'Nombre del estudiante',0);
// QUINTA PARTE DEL CUADRO. NUMERO CORRELATIVO.
    $this->Rect(91,$w2[0],60,13);
    $this->RotatedText(105,65,'Apellidos del estudiante',0);
// SEXTA PARTE DEL CUADRO. NMERO DE IDENTIFICACION ESTUDIANTIL. NIE.
    $this->cMargin=1;
    $this->Rect(151,$w2[0],27,13);
    $this->SetXY(153,58);
    $this->MultiCell(22,3,'Fecha de Nacimiento',7,'C',0);
// SEXTA 1 PARTE DEL CUADRO. NUMERO CORRELATIVO.
    $this->Rect(151,$w2[1],9,6);
    $this->RotatedText(153,68,'dd',0);    
// SEXTA 2 PARTE DEL CUADRO. NUMERO CORRELATIVO.
    $this->Rect(160,$w2[1],9,6);
    $this->RotatedText(161,68,'mm',0);
// SEXTA 3 PARTE DEL CUADRO. NUMERO CORRELATIVO.
    $this->Rect(169,$w2[1],9,6);
    $this->RotatedText(170,68,'aaaa',0);
// SEPTIMA 1 PARTE DEL CUADRO. SEXO.
    $this->Rect(178,$w2[1],9,6);
    $this->RotatedText(180,68,'M',0);    
// SEPTIMA 2 PARTE DEL CUADRO. SEXO.
    $this->Rect(187,$w2[1],9,6);
    $this->RotatedText(190,68,'F',0);
// OCTAVA PARTE DEL CUADRO. NMERO DE IDENTIFICACION ESTUDIANTIL. NIE.
    $this->cMargin=1;
    $this->Rect(178,$w2[0],18,13);
    $this->SetXY(175,58);
    $this->MultiCell(22,3,'Sexo',7,'C',0);
// NOVENA  PARTE DEL CUADRO. GRADO.
    $this->Rect(196,$w2[0],12,13);
    $this->RotatedText(197,65,'Grado',0);
// NOVENA PARTE DEL CUADRO. NOMBRE DEL RESPONSABLE.
    $this->Rect(208,$w2[0],93,13);
    $this->RotatedText(230,65,'Padre, madre o responsable',0);
// DECIMA PARTE DEL CUADRO. PARTIDA DE NACIMIENTO
    $this->cMargin=1;
    $this->Rect(301,$w2[0],40,13);
    $this->SetXY(304,58);
    $this->MultiCell(35,3,'Partida de Nacimiento',7,'C',0);    
// DECIMA 1 PARTE DEL CUADRO. PARTIDA DE NACIMIENTO.
    $this->Rect(301,$w2[1],20,6);
    $this->RotatedText(310,68,'Si',0);
// DECIMA 2 PARTE DEL CUADRO. PARTIDA DE NACIMIENTO.
    $this->Rect(321,$w2[1],20,6);
    $this->RotatedText(330,68,'No',0);
}

// cabecera de la boleta de captura de datos.
function encabezado()
{
    $this->ln();
    $this->SetFont('Arial','',9); // I : Italica; U: Normal;
///////////////////CREAR LA CABECERA DE LA TABLA.
// PRIMERA PARTE DEL RECTANGULO.
    $this->Rect(5,37,336,13);
    $this->setxy(5,50);
// SEGUNDA PARTE DEL CUADRO. NUMERO CORRELATIVO.
    $this->Rect(5,37,5,13);
    $this->RotatedText(6,45,'N°',0);
// TERCERA PARTE DEL CUADRO. NMERO DE IDENTIFICACION ESTUDIANTIL. NIE.
    $this->cMargin=1;
    $this->Rect(10,37,21,13);
    $this->SetXY(9.5,38);
    $this->MultiCell(22,3,'Número de Identificación del Estudiantil (NIE)',7,'C',0);
// CUARTA PARTE DEL CUADRO. NUMERO CORRELATIVO.
    $this->Rect(31,37,60,13);
    $this->RotatedText(44,45,'Nombre del estudiante',0);
// QUINTA PARTE DEL CUADRO. NUMERO CORRELATIVO.
    $this->Rect(91,37,60,13);
    $this->RotatedText(105,45,'Apellidos del estudiante',0);
// SEXTA PARTE DEL CUADRO. NMERO DE IDENTIFICACION ESTUDIANTIL. NIE.
    $this->cMargin=1;
    $this->Rect(151,37,27,13);
    $this->SetXY(153,38);
    $this->MultiCell(22,3,'Fecha de Nacimiento',7,'C',0);
// SEXTA 1 PARTE DEL CUADRO. NUMERO CORRELATIVO.
    $this->Rect(151,44,9,6);
    $this->RotatedText(153,48,'dd',0);    
// SEXTA 2 PARTE DEL CUADRO. NUMERO CORRELATIVO.
    $this->Rect(160,44,9,6);
    $this->RotatedText(161,48,'mm',0);
// SEXTA 3 PARTE DEL CUADRO. NUMERO CORRELATIVO.
    $this->Rect(169,44,9,6);
    $this->RotatedText(170,48,'aaaa',0);
// SEPTIMA 1 PARTE DEL CUADRO. SEXO.
    $this->Rect(178,44,9,6);
    $this->RotatedText(180,48,'M',0);    
// SEPTIMA 2 PARTE DEL CUADRO. SEXO.
    $this->Rect(187,44,9,6);
    $this->RotatedText(190,48,'F',0);
// OCTAVA PARTE DEL CUADRO. NMERO DE IDENTIFICACION ESTUDIANTIL. NIE.
    $this->cMargin=1;
    $this->Rect(178,37,18,13);
    $this->SetXY(175,38);
    $this->MultiCell(22,3,'Sexo',7,'C',0);
// NOVENA  PARTE DEL CUADRO. GRADO.
    $this->Rect(196,37,12,13);
    $this->RotatedText(197,45,'Grado',0);
// NOVENA PARTE DEL CUADRO. NOMBRE DEL RESPONSABLE.
    $this->Rect(208,37,93,13);
    $this->RotatedText(230,45,'Padre, madre o responsable',0);
// DECIMA PARTE DEL CUADRO. PARTIDA DE NACIMIENTO
    $this->cMargin=1;
    $this->Rect(301,37,40,13);
    $this->SetXY(304,38);
    $this->MultiCell(35,3,'Partida de Nacimiento',7,'C',0);    
// DECIMA 1 PARTE DEL CUADRO. PARTIDA DE NACIMIENTO.
    $this->Rect(301,44,10,6);
    $this->RotatedText(304,48,'Nº',0);
// DECIMA 2 PARTE DEL CUADRO. PARTIDA DE NACIMIENTO.
    $this->Rect(311,44,10,6);
    $this->RotatedText(312,48,'Folio',0);
// DECIMA 3 PARTE DEL CUADRO. PARTIDA DE NACIMIENTO.
    $this->Rect(321,44,10,6);
    $this->RotatedText(322,48,'Tomo',0);
// DECIMA 4 PARTE DEL CUADRO. PARTIDA DE NACIMIENTO.
    $this->Rect(331,44,10,6);
    $this->RotatedText(332,48,'Libro',0);   
}

// cabecera de la boleta de captura de datos.
function encabezado_2()
{
    $this->ln();
    $this->SetFont('Arial','',9); // I : Italica; U: Normal;
///////////////////CREAR LA CABECERA DE LA TABLA.
// PRIMERA PARTE DEL RECTANGULO.
    $this->Rect(5,17,336,13);
    $this->setxy(5,30);
// SEGUNDA PARTE DEL CUADRO. NUMERO CORRELATIVO.
    $this->Rect(5,17,5,13);
    $this->RotatedText(6,25,'N°',0);
// TERCERA PARTE DEL CUADRO. NMERO DE IDENTIFICACION ESTUDIANTIL. NIE.
    $this->cMargin=1;
    $this->Rect(10,17,21,13);
    $this->SetXY(9.5,18);
    $this->MultiCell(22,3,'Número de Identificación del Estudiantil (NIE)',7,'C',0);
// CUARTA PARTE DEL CUADRO. NUMERO CORRELATIVO.
    $this->Rect(31,17,60,13);
    $this->RotatedText(44,25,'Nombre del estudiante',0);
// QUINTA PARTE DEL CUADRO. NUMERO CORRELATIVO.
    $this->Rect(91,17,60,13);
    $this->RotatedText(105,25,'Apellidos del estudiante',0);
// SEXTA PARTE DEL CUADRO. NMERO DE IDENTIFICACION ESTUDIANTIL. NIE.
    $this->cMargin=1;
    $this->Rect(151,17,27,13);
    $this->SetXY(153,18);
    $this->MultiCell(22,3,'Fecha de Nacimiento',7,'C',0);
// SEXTA 1 PARTE DEL CUADRO. NUMERO CORRELATIVO.
    $this->Rect(151,24,9,6);
    $this->RotatedText(153,28,'dd',0);    
// SEXTA 2 PARTE DEL CUADRO. NUMERO CORRELATIVO.
    $this->Rect(160,24,9,6);
    $this->RotatedText(161,28,'mm',0);
// SEXTA 3 PARTE DEL CUADRO. NUMERO CORRELATIVO.
    $this->Rect(169,24,9,6);
    $this->RotatedText(170,28,'aaaa',0);
// SEPTIMA 1 PARTE DEL CUADRO. SEXO.
    $this->Rect(178,24,9,6);
    $this->RotatedText(180,28,'M',0);    
// SEPTIMA 2 PARTE DEL CUADRO. SEXO.
    $this->Rect(187,24,9,6);
    $this->RotatedText(190,28,'F',0);
// OCTAVA PARTE DEL CUADRO. NMERO DE IDENTIFICACION ESTUDIANTIL. NIE.
    $this->cMargin=1;
    $this->Rect(178,17,18,13);
    $this->SetXY(175,18);
    $this->MultiCell(22,3,'Sexo',7,'C',0);
// NOVENA  PARTE DEL CUADRO. GRADO.
    $this->Rect(196,17,12,13);
    $this->RotatedText(197,25,'Grado',0);
// NOVENA PARTE DEL CUADRO. NOMBRE DEL RESPONSABLE.
    $this->Rect(208,17,93,13);
    $this->RotatedText(230,25,'Padre, madre o responsable',0);
// DECIMA PARTE DEL CUADRO. PARTIDA DE NACIMIENTO
    $this->cMargin=1;
    $this->Rect(301,17,40,13);
    $this->SetXY(304,18);
    $this->MultiCell(35,3,'Partida de Nacimiento',7,'C',0);    
// DECIMA 1 PARTE DEL CUADRO. PARTIDA DE NACIMIENTO.
    $this->Rect(301,24,10,6);
    $this->RotatedText(304,28,'Nº',0);
// DECIMA 2 PARTE DEL CUADRO. PARTIDA DE NACIMIENTO.
    $this->Rect(311,24,10,6);
    $this->RotatedText(312,28,'Folio',0);
// DECIMA 3 PARTE DEL CUADRO. PARTIDA DE NACIMIENTO.
    $this->Rect(321,24,10,6);
    $this->RotatedText(322,28,'Tomo',0);
// DECIMA 4 PARTE DEL CUADRO. PARTIDA DE NACIMIENTO.
    $this->Rect(331,24,10,6);
    $this->RotatedText(332,28,'Libro',0);
    $this->SetFont('Arial','',10); // I : Italica; U: Normal;
}
   // rotar texto funcion TEXT()
function RotatedText($x,$y,$txt,$angle)
{
	//Text rotated around its origin
	$this->Rotate($angle,$x,$y);
  $this->Text($x,$y,$txt);
	$this->Rotate(0);
}

function RotatedTextMultiCell($x,$y,$txt,$angle)
{
	//Text rotated around its origin
	$this->Rotate($angle,$x,$y);
	$this->SetXY($x,$y);
        $this->MultiCell(33,4,$txt,0,'J');
	$this->Rotate(0);
}

function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
{
	$k=$this->k;
	if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak())
	{
		$x=$this->x;
		$ws=$this->ws;
		if($ws>0)
		{
			$this->ws=0;
			$this->_out('0 Tw');
		}
		$this->AddPage($this->CurOrientation);
		$this->x=$x;
		if($ws>0)
		{
			$this->ws=$ws;
			$this->_out(sprintf('%.3F Tw',$ws*$k));
		}
	}
	if($w==0)
        $w=$this->w-$this->rMargin-$this->x;
	$s='';
	if($fill || $border==1)
	{
		if($fill)
			$op=($border==1) ? 'B' : 'f';
		else
			$op='S';
		$s=sprintf('%.2F %.2F %.2F %.2F re %s ',$this->x*$k,($this->h-$this->y)*$k,$w*$k,-$h*$k,$op);
	}
	if(is_string($border))
	{
		$x=$this->x;
		$y=$this->y;
		if(is_int(strpos($border,'L')))
			$s.=sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,$x*$k,($this->h-($y+$h))*$k);
		if(is_int(strpos($border,'T')))
			$s.=sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-$y)*$k);
		if(is_int(strpos($border,'R')))
			$s.=sprintf('%.2F %.2F m %.2F %.2F l S ',($x+$w)*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
		if(is_int(strpos($border,'B')))
			$s.=sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-($y+$h))*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
	}
	if($txt!='')
	{
		if($align=='R')
			$dx=$w-$this->cMargin-$this->GetStringWidth($txt);
		elseif($align=='C')
			$dx=($w-$this->GetStringWidth($txt))/2;
		elseif($align=='FJ')
		{
			//Set word spacing
			$wmax=($w-2*$this->cMargin);
			$this->ws=($wmax-$this->GetStringWidth($txt))/substr_count($txt,' ');
			$this->_out(sprintf('%.3F Tw',$this->ws*$this->k));
			$dx=$this->cMargin;
		}
		else
			$dx=$this->cMargin;
		$txt=str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$txt)));
		if($this->ColorFlag)
			$s.='q '.$this->TextColor.' ';
		$s.=sprintf('BT %.2F %.2F Td (%s) Tj ET',($this->x+$dx)*$k,($this->h-($this->y+.5*$h+.3*$this->FontSize))*$k,$txt);
		if($this->underline)
			$s.=' '.$this->_dounderline($this->x+$dx,$this->y+.5*$h+.3*$this->FontSize,$txt);
		if($this->ColorFlag)
			$s.=' Q';
		if($link)
		{
			if($align=='FJ')
				$wlink=$wmax;
			else
				$wlink=$this->GetStringWidth($txt);
			$this->Link($this->x+$dx,$this->y+.5*$h-.5*$this->FontSize,$wlink,$this->FontSize,$link);
		}
	}
	if($s)
		$this->_out($s);
	if($align=='FJ')
	{
		//Remove word spacing
		$this->_out('0 Tw');
		$this->ws=0;
	}
	$this->lasth=$h;
	if($ln>0)
	{
		$this->y+=$h;
		if($ln==1)
			$this->x=$this->lMargin;
	}
	else
		$this->x+=$w;
}
}
?>
