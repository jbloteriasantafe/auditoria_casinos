<?php

namespace App\Http\Controllers;



use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use PDF;
use Dompdf\Dompdf;
use View;
use Zipper;
use File;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use App\Plataforma;
use App\Archivo;

function bce(){//a 10000 digitos...  
  return '2.718281828459045235360287471352662497757247093699959574966967627724076630353547594571382178525166427427466391932003059921817413596629043572900334295260595630738132328627943490763233829880753195251019011573834187930702154089149934884167509244761460668082264800168477411853742345442437107539077744992069551702761838606261331384583000752044933826560297606737113200709328709127443747047230696977209310141692836819025515108657463772111252389784425056953696770785449969967946864454905987931636889230098793127736178215424999229576351482208269895193668033182528869398496465105820939239829488793320362509443117301238197068416140397019837679320683282376464804295311802328782509819455815301756717361332069811250996181881593041690351598888519345807273866738589422879228499892086805825749279610484198444363463244968487560233624827041978623209002160990235304369941849146314093431738143640546253152096183690888707016768396424378140592714563549061303107208510383750510115747704171898610687396965521267154688957035035402123407849819334321068170121005627880235193033224745015853904730419957777093503660416997329725088687696640355570716226844716256079882651787134195124665201030592123667719432527867539855894489697096409754591856956380236370162112047742722836489613422516445078182442352948636372141740238893441247963574370263755294448337998016125492278509257782562092622648326277933386566481627725164019105900491644998289315056604725802778631864155195653244258698294695930801915298721172556347546396447910145904090586298496791287406870504895858671747985466775757320568128845920541334053922000113786300945560688166740016984205580403363795376452030402432256613527836951177883863874439662532249850654995886234281899707733276171783928034946501434558897071942586398772754710962953741521115136835062752602326484728703920764310059584116612054529703023647254929666938115137322753645098889031360205724817658511806303644281231496550704751025446501172721155519486685080036853228183152196003735625279449515828418829478761085263981395599006737648292244375287184624578036192981971399147564488262603903381441823262515097482798777996437308997038886778227138360577297882412561190717663946507063304527954661855096666185664709711344474016070462621568071748187784437143698821855967095910259686200235371858874856965220005031173439207321139080329363447972735595527734907178379342163701205005451326383544000186323991490705479778056697853358048966906295119432473099587655236812859041383241160722602998330535370876138939639177957454016137223618789365260538155841587186925538606164779834025435128439612946035291332594279490433729908573158029095863138268329147711639633709240031689458636060645845925126994655724839186564209752685082307544254599376917041977780085362730941710163434907696423722294352366125572508814779223151974778060569672538017180776360346245927877846585065605078084421152969752189087401966090665180351650179250461950136658543663271254963990854914420001457476081930221206602433009641270489439039717719518069908699860663658323227870937650226014929101151717763594460202324930028040186772391028809786660565118326004368850881715723866984224220102495055188169480322100251542649463981287367765892768816359831247788652014117411091360116499507662907794364600585194199856016264790761532103872755712699251827568798930276176114616254935649590379804583818232336861201624373656984670378585330527583333793990752166069238053369887956513728559388349989470741618155012539706464817194670834819721448889879067650379590366967249499254527903372963616265897603949857674139735944102374432970935547798262961459144293645142861715858733974679189757121195618738578364475844842355558105002561149239151889309946342841393608038309166281881150371528496705974162562823609216807515017772538740256425347087908913729172282861151591568372524163077225440633787593105982676094420326192428531701878177296023541306067213604600038966109364709514141718577701418060644363681546444005331608778314317444081194942297559931401188868331483280270655383300469329011574414756313999722170380461709289457909627166226074071874997535921275608441473782330327033016823719364800217328573493594756433412994302485023573221459784328264142168487872167336701061509424345698440187331281010794512722373788612605816566805371439612788873252737389039289050686532413806279602593038772769778379286840932536588073398845721874602100531148335132385004782716937621800490479559795929059165547050577751430817511269898518840871856402603530558373783242292418562564425502267215598027401261797192804713960068916382866527700975276706977703643926022437284184088325184877047263844037953016690546593746161932384036389313136432713768884102681121989127522305625675625470172508634976536728860596675274086862740791285657699631378975303466061666980421826772456053066077389962421834085988207186468262321508028828635974683965435885668550377313129658797581050121491620765676995065971534476347032085321560367482860837865680307306265763346977429563464371670939719306087696349532884683361303882943104080029687386911706666614680001512114344225602387447432525076938707777519329994213727721125884360871583483562696166198057252661220679754062106208064988291845439530152998209250300549825704339055357016865312052649561485724925738620691740369521353373253166634546658859728665945113644137033139367211856955395210845840724432383558606310680696492485123263269951460359603729725319836842336390463213671011619282171115028280160448805880238203198149309636959673583274202498824568494127386056649135252670604623445054922758115170931492187959271800194096886698683703730220047531433818109270803001720593553052070070607223399946399057131158709963577735902719628506114651483752620956534671329002599439766311454590268589897911583709341937044115512192011716488056694593813118384376562062784631049034629395002945834116482411496975832601180073169943739350696629571241027323913874175492307186245454322203955273529524024590380574450289224688628533654221381572213116328811205214648980518009202471939171055539011394331668151582884368760696110250517100739276238555338627255353883096067164466237092264680967125406186950214317621166814009759528149390722260111268115310838731761732323526360583817315103459573653822353499293582283685100781088463434998351840445170427018938199424341009057537625776757111809008816418331920196262341628816652137471732547772778348877436651882875215668571950637193656539038944936642176400312152787022236646363575550356557694888654950027085392361710550213114741374410613444554419210133617299628569489919336918472947858072915608851039678195942983318648075608367955149663644896559294818785178403877332624705194505041984774201418394773120281588684570729054405751060128525805659470304683634459265255213700806875200959345360731622611872817392807462309468536782310609792159936001994623799343421068781349734695924646975250624695861690917857397659519939299399556754271465491045686070209901260681870498417807917392407194599632306025470790177452751318680998228473086076653686685551646770291133682756310722334672611370549079536583453863719623585631261838715677411873852772292259474337378569553845624680101390572787101651296663676445187246565373040244368414081448873295784734849000301947788802046032466084287535184836495919508288832320652212810419044804724794929134228495197002260131043006241071797150279343326340799596053144605323048852897291765987601666781193793237245385720960758227717848336161358261289622611812945592746276713779448758675365754486140761193112595851265575973457301533364263076798544338576171533346232527057200530398828949903425956623297578248873502925916682589445689465599265845476269452878051650172067478541788798227680653665064191097343452887833862172615626958265447820567298775642632532159429441803994321700009054265076309558846589517170914760743713689331946909098190450129030709956622662030318264936573369841955577696378762491885286568660760056602560544571133728684020557441603083705231224258722343885412317948138855007568938112493538631863528708379984569261998179452336408742959118074745341955142035172618420084550917084568236820089773945584267921427347756087964427920270831215015640634134161716644806981548376449157390012121704154787259199894382536495051477137939914720521952907939613762110723849429061635760459623125350606853765142311534966568371511660422079639446662116325515772907097847315627827759878813649195125748332879377157145909106484164267830994972367442017586226940215940792448054125536043131799269673915754241929660731239376354213923061787675395871143610408940996608947141834069836299367536262154524729846421375289107988438130609555262272083751862983706678722443019579379378607210725427728907173285487437435578196651171661833088112912024520404868220007234403502544820283425418788465360259150644527165770004452109773558589762265548494162171498953238342160011406295071849042778925855274303522139683567901807640604213830730877446017084268827226117718084266433365178000217190344923426426629226145600433738386833555534345300426481847398921562708609565062934040526494324426144566592129122564889356965500915430642613425266847259491431423939884543248632746184284665598533231221046625989014171210344608427161661900125719587079321756969854401339762209674945418540711844643394699016269835160784892451405894094639526780735457970030705116368251948770118976400282764841416058720618418529718915401968825328930914966534575357142731848201638464483249903788606900807270932767312758196656394114896171683298045513972950668760474091542042842999354102582911350224169076943166857424252250902693903481485645130306992519959043638402842926741257342244776558417788617173726546208549829449894678735092958165263207225899236876845701782303809656788311228930580914057261086588484587310165815116753332767488701482916741970151255978257270740643180860142814902414678047232759768426963393577354293018673943971638861176420900406866339885684168100387238921448317607011668450388721236436704331409115573328018297798873659091665961240202177855885487617616198937079438005666336488436508914480557103976521469602766258359905198704230017946553679';
}

function bcepow(string $val,int $scale){//bcpow(bce(),$val,$scale) tira resultados mal >:(
  static $inv_factorial = null;
  static $ITERATIONS = 100;
  if($inv_factorial === null){
    $inv_factorial = array_fill(0,$ITERATIONS+2,null);
    $inv_factorial[0] = '1';
    $inv_factorial[1] = '1';
    for($i = 2;$i < $ITERATIONS;$i++){
      $inv_factorial[$i] = bcdiv($inv_factorial[$i-1],$i,$scale);
    }
  }
   
  $pow = $val;
  $retval = bcadd('1', $val, $scale);
  for($i = 0;$i < ($ITERATIONS-2);$i++) {
    $pow = bcmul($pow,$val,$scale);
    $inv_fact = $inv_factorial[$i+2];
    $retval = bcadd($retval,bcmul($pow, $inv_fact, $scale),$scale);
  }
  return $retval;
}

function bcln(string $y,int $outscale,int $scale = null){  
  //f(x) = e^$x - $y = 0
  if($scale === null){
    $scale = max(100,$outscale*5);
  }
    
  $e = function($x) use (&$scale){
    return bcepow($x,$scale);
  };
  $f = function($e_x) use ($y,&$scale){
    return bcsub($e_x,$y,$scale);
  };
  $sign = function($v) use (&$scale){
    return bccomp($v,'0',$scale);
  };
  
  $min_x = '-100';
  $x;
  $max_x = '27';
  
  $e_min_x = '0.00000000000018795288165390832947582704184221926212287172492981107134025344486807932831544670660830457762492512822651240163545646249832503627252904100285380862571405094604602739559217914821368117889473689492480504400965450755586633789201239121728712122643443758598473461391714129437574533234934070893974775689923224319515916858352135247817364383365777075151569771219203333797398440887343211149724641234895069459929901416593130050157726455550450575389293088597606784863729302610354955487749240776080356847767476807322335158607';
  $e_x;
  $e_max_x = '532048240601.798616683747304341177441659255804283688808837731150535969409966953197164611553828831352151693637064072599447272272788684828844196909131943172964617211590451251606340261813200391872728258573188228469394714647969081103842230553006046256565945';
  
  $f_min_x = $f($e_min_x);
  $f_x;
  $f_max_x = $f($e_max_x);
  
  $s_min_x = $sign($f_min_x);
  $s_x;
  $s_max_x = $sign($f_max_x);
  
  assert($s_min_x == -1);//Failed bound check
  assert($s_max_x ==  1);//Failed bound check
  
  for($it = 0;$it < $scale;$it++){//NEWTON RAPHSON me da sobreshoot para cualquier lado :/ 
    $x = $e_x = $f_x = $s_x = null;
    $x = bcmul(bcadd($min_x,$max_x,$scale),'0.5',$scale);
    $e_x = $e($x);
    $f_x = $f($e_x);
    $s_x = $sign($f_x);
    
    if($s_x  == 0){
      break;
    }
    else if($s_x == -1){
      $min_x   = $x;
      $e_min_x = $e_x;
      $f_min_x = $f_x;
      $s_min_x = $s_x;
    }
    else if($s_x == 1){
      $max_x   = $x;
      $e_max_x = $e_x;
      $f_max_x = $f_x;
      $s_max_x = $s_x;
   
    }
  }
  return bcadd($x,0,$outscale);
}

class CanonController extends Controller
{
  static $max_scale = 64;
  private static $atributos = [];
  private static $instance;

  public static function getInstancia(){
    self::$instance = self::$instance ?? (new self()); 
    return self::$instance;
  }
    
  public function index(){
    $casinos = UsuarioController::getInstancia()->quienSoy()['usuario']->casinos;
    $plataformas = Plataforma::all();                 
    return View::make('Canon.ncanon', compact('casinos','plataformas'));
  }
  
  public function recalcular(Request $request){
    $R = function($s,$dflt = null) use (&$request){
      return (($request[$s] ?? null) === null || ($request[$s] == '') || ($request[$s] == []))? $dflt : $request[$s];
    };
        
    $año_mes = $R('año_mes');//@RETORNADO
    $id_casino = $R('id_casino');//@RETORNADO
    $estado = $R('estado','Nuevo');//@RETORNADO
    $fecha_cotizacion = $R('fecha_cotizacion');//@RETORNADO
    $fecha_vencimiento = $R('fecha_vencimiento');//@RETORNADO
    $fecha_pago = $R('fecha_pago');//@RETORNADO
    $es_antiguo = $R('es_antiguo',0)? 1 : 0;//@RETORNADO
    $adjuntos = $R('adjuntos',[]);//@RETORNADO
    
    if($año_mes !== null && $año_mes !== ''){
      $f = explode('-',$año_mes);
      $f[2] = '10';
      $f = implode('-',$f);
      $f = new \DateTimeImmutable($f);
      $viernes_anterior = clone $f;
      $proximo_lunes = clone $f;
      for($break = 9;$break > 0 && in_array($viernes_anterior->format('w'),['0','6']);$break--){
        $viernes_anterior = $viernes_anterior->sub(\DateInterval::createFromDateString('1 day'));
      }
      for($break = 9;$break > 0 && in_array($proximo_lunes->format('w'),['0','6']);$break--){
        $proximo_lunes = $proximo_lunes->add(\DateInterval::createFromDateString('1 day'));
      }
      $fecha_cotizacion = $R('fecha_cotizacion',$viernes_anterior->format('Y-m-d'));//@RETORNADO
      $fecha_vencimiento = $R('fecha_vencimiento',$proximo_lunes->format('Y-m-d'));//@RETORNADO
      $fecha_pago = $R('fecha_pago',$fecha_vencimiento);//@RETORNADO
    }
    
    $bruto_devengado = '0.00';//@RETORNADO
    $bruto_pagar = '0.00';//@RETORNADO
    $canon_variable = [];//@RETORNADO
    $canon_fijo_mesas = [];//@RETORNADO
    $canon_fijo_mesas_adicionales = [];//@RETORNADO
    if($es_antiguo){
      $bruto_devengado = bcadd($R('bruto_devengado',$bruto_devengado),'0',2);
      $bruto_pagar = bcadd($R('bruto_pagar',$bruto_devengado),'0',2);
    }
    else{
      {//Varios tipos (JOL, Bingo, Maquinas)
        $defecto = ($this->valorPorDefecto('canon_variable') ?? [])[$id_casino] ?? [];
        foreach(($request['canon_variable'] ?? $defecto ?? []) as $tipo => $_){
          $canon_variable[$tipo] = $this->canon_variable_recalcular(
            $tipo,
            $defecto[$tipo] ?? [],
            ($request['canon_variable'] ?? [])[$tipo] ?? []
          );
          $bruto_devengado = bcadd($bruto_devengado,$canon_variable[$tipo]['total_devengado'] ?? '0',2);
          $bruto_pagar     = bcadd($bruto_pagar,$canon_variable[$tipo]['total_pagar'] ?? '0',2);
        }
      }
      {//Dos tipos muy parecidos (Fijas y Diarias), se hace asi mas que nada para que sea homogeneo
        $defecto = $this->valorPorDefecto('canon_fijo_mesas')[$id_casino] ?? [];
        foreach(($request['canon_fijo_mesas'] ?? $defecto ?? []) as $tipo => $_){
          $canon_fijo_mesas[$tipo] = $this->mesas_recalcular(
            $año_mes,
            $id_casino,
            $fecha_cotizacion,
            $tipo,
            $defecto[$tipo] ?? [],
            ($request['canon_fijo_mesas'] ?? [])[$tipo] ?? []
          );
          $bruto_devengado = bcadd($bruto_devengado,$canon_fijo_mesas[$tipo]['total_devengado'] ?? '0',2);
          $bruto_pagar     = bcadd($bruto_pagar,$canon_fijo_mesas[$tipo]['total_pagar'] ?? '0',2);
        }
      }
      {//Las mesas adicionales pueden ser varios tipos (Torneo Truco, Torneo Poker, etc)
        $defecto = $this->valorPorDefecto('canon_fijo_mesas_adicionales')[$id_casino] ?? [];
        foreach(($request['canon_fijo_mesas_adicionales'] ?? $defecto ?? []) as $tipo => $_){
          $canon_fijo_mesas_adicionales[$tipo] = $this->mesasAdicionales_recalcular(
            $tipo,
            $defecto[$tipo] ?? [],
            ($request['canon_fijo_mesas_adicionales'] ?? [])[$tipo] ?? []
          );
          $bruto_devengado = bcadd($bruto_devengado,$canon_fijo_mesas_adicionales[$tipo]['total_devengado'] ?? '0',2);
          $bruto_pagar     = bcadd($bruto_pagar,$canon_fijo_mesas_adicionales[$tipo]['total_pagar'] ?? '0',2);
        }
      }
    }
    
    $deduccion = bcadd($R('deduccion','0.00'),'0',2);//@RETORNADO
    $devengado = bcsub($bruto_devengado,$deduccion,2);//@RETORNADO
    
    $porcentaje_seguridad = bccomp($bruto_devengado,'0.00') > 0?//@RETORNADO
       bcdiv(bcmul('100.0',$deduccion),$bruto_devengado,5)
      : null;
    
    $interes_mora = bcadd($R('interes_mora','0.0000'),'0',4);//@RETORNADO
    $a_pagar = bcadd($R('a_pagar','0.00'),'0',2);//@RETORNADO
    $mora = bcadd($R('mora','0.00'),'0',2);//@RETORNADO
    
    if(bccomp($bruto_pagar,'0.00',2) <= 0){
      $a_pagar = '0.00';
      $interes_mora = '0.00';
      $mora = '0.00';
    }
    else if($fecha_vencimiento && $fecha_pago){
      $timestamp_venc = \DateTimeImmutable::createFromFormat('Y-m-d', $fecha_vencimiento);
      $timestamp_pago = \DateTimeImmutable::createFromFormat('Y-m-d', $fecha_pago);
      $date_interval  = $timestamp_pago->diff($timestamp_venc);
      $cantidad_dias = intval($date_interval->format('%d'));
      if($cantidad_dias < 0){}
      else if($cantidad_dias == 0){
        $a_pagar = $bruto_pagar;
        $interes_mora = '0.0000';
        $mora = '0.00';
      }
      else if($R('interes_mora',null) !== null){//Si envio el interes, calculo el pago
        //$a_pagar = $bruto_pagar*pow(1+$interes_mora/100.0,$cantidad_dias);
        $base = bcadd('1',bcdiv($interes_mora,'100',6),6);
        $a_pagar = $bruto_pagar;
        for($i=0;$i<$cantidad_dias;$i++){
          $a_pagar = bcmul($a_pagar,$base,self::$max_scale);
        }
        $a_pagar = bcadd($a_pagar,0,2);
        $mora = bcsub($a_pagar,$bruto_pagar,2);
      }
      else if($R('a_pagar',null) !== null){//Si envio el pago, calculo el interes
        //$coeff = log($a_pagar/$bruto_pagar)/$cantidad_dias;
        //$interes_mora = (exp($coeff)-1)*100;
        //$mora = $a_pagar - $bruto_pagar;
        $coeff = bcln(bcdiv($a_pagar,$bruto_pagar,self::$max_scale),16);
        $coeff = bcdiv($coeff,$cantidad_dias,self::$max_scale);
        $interes_mora = bcepow($coeff,self::$max_scale);
        $interes_mora = bcsub($interes_mora,'1',self::$max_scale);
        $interes_mora = bcmul($interes_mora,'100',4);
        $mora = bcsub($a_pagar,$bruto_pagar,2);
      }
      else if($R('mora',null) !== null){
        //$coeff = log($a_pagar/$bruto_pagar)/$cantidad_dias;
        //$interes_mora = (exp($coeff)-1)*100;
        $a_pagar = bcadd($bruto_pagar,$mora,2);
        
        $coeff = bcln(bcdiv($a_pagar,$bruto_pagar,self::$max_scale),16);
        $coeff = bcdiv($coeff,$cantidad_dias,self::$max_scale);
        $interes_mora = bcepow($coeff,self::$max_scale);
        $interes_mora = bcsub($interes_mora,'1',self::$max_scale);
        $interes_mora = bcmul($interes_mora,'100',4);
      }
      else {//Son todos nulos... asumo interes 0...
        $a_pagar = $bruto_pagar;
        $interes_mora = '0.0000';
        $mora = '0.00';
      }
    }
    
    $pago = bcadd($R('pago','0.00'),'0',2);//@RETORNADO
    $diferencia = bcsub($pago,$a_pagar,2);//@RETORNADO
    $saldo_anterior = '0.00';//@RETORNADO
    if($año_mes !== null && $id_casino !== null){
      $saldo_anterior = $this->calcular_saldo_hasta($año_mes,$id_casino);
    }
    
    $saldo_posterior = bcadd($saldo_anterior,$diferencia,2);//@RETORNADO
    
    return compact(
      'año_mes','id_casino','estado','es_antiguo',
      'canon_variable','canon_fijo_mesas','canon_fijo_mesas_adicionales','adjuntos',
      'bruto_devengado','deduccion','devengado','porcentaje_seguridad',
      'bruto_pagar','fecha_vencimiento','fecha_pago','interes_mora','mora',
      'a_pagar','pago','diferencia','saldo_anterior','saldo_posterior'
    );
  }
  
  private function calcular_saldo_hasta($año_mes,$id_casino){
    $saldo_anterior = DB::table('canon')
    ->selectRaw('SUM(diferencia) as saldo')//esto deberia ser DECIMAL asi que retorna un string
    ->where('id_casino',$id_casino)
    ->where('año_mes','<',$año_mes)
    ->groupBy(DB::raw('"constant"'))
    ->first();
    return $saldo_anterior === null? 0 : $saldo_anterior->saldo;
  }
  
  public function canon_variable_recalcular($tipo,$valores_defecto,$data){
    $R = function($s,$dflt = null) use (&$data){
      return (($data[$s] ?? null) === null || ($data[$s] == '') || ($data[$s] == []))? $dflt : $data[$s];
    };
    $D = function($s,$dflt = null) use (&$valores_defecto){
      return (($valores_defecto[$s] ?? null) === null || ($valores_defecto[$s] == '') || ($valores_defecto[$s] == []))? $dflt : $valores_defecto[$s];
    };
    $RD = function($s,$dflt = null) use ($R,$D){
      return $R($s,null) ?? $D($s,null) ?? $dflt;
    };
    $apostado_sistema = bcadd($R('apostado_sistema','0.00'),'0',2);
    $apostado_informado = bcadd($R('apostado_informado','0.00'),'0',2);
    //El apostado es de DECIMAL(7,4)
    //@TODO: agregar asserts o sacar precision de la BD
    $apostado_porcentaje_aplicable = bcadd($RD('apostado_porcentaje_aplicable','0.0000'),'0',4);
    $base_imponible_devengado = bcdiv(bcmul($apostado_sistema,$apostado_porcentaje_aplicable,self::$max_scale),'100',2);
    $base_imponible_pagar     = bcdiv(bcmul($apostado_informado,$apostado_porcentaje_aplicable,self::$max_scale),'100',2);
    
    $apostado_porcentaje_impuesto_ley = bcadd($RD('apostado_porcentaje_impuesto_ley','0.0000'),'0',4);
    $impuesto_devengado = bcdiv(bcmul($base_imponible_devengado,$apostado_porcentaje_impuesto_ley,self::$max_scale),'100',2);
    $impuesto_pagar = bcdiv(bcmul($base_imponible_pagar,$apostado_porcentaje_impuesto_ley,self::$max_scale),'100',2);
    
    $bruto = bcadd($R('bruto','0.00'),'0',2);
    $subtotal_devengado = bcsub($bruto,$impuesto_devengado,2);
    $subtotal_pagar     = bcsub($bruto,$impuesto_pagar,2);
    
    $alicuota = bcadd($RD('alicuota','0.0000'),'0',4);
    $total_devengado = bcdiv(bcmul($subtotal_devengado,$alicuota,self::$max_scale),'100',2);
    $total_pagar = bcdiv(bcmul($subtotal_pagar,$alicuota,self::$max_scale),'100',2);
    
    return compact('tipo',
      'apostado_sistema','apostado_informado',
      'apostado_porcentaje_aplicable','base_imponible_devengado','base_imponible_pagar',
      'apostado_porcentaje_impuesto_ley','impuesto_devengado','impuesto_pagar',
      'bruto','subtotal_devengado','subtotal_pagar',
      'alicuota','total_devengado','total_pagar'
    );
  }
  
  public function mesas_recalcular(
      $año_mes,$id_casino,
      $fecha_cotizacion,//@RETORNADO
      $tipo,//@RETORNADO
      $valores_defecto,
      $data
  ){
    $R = function($s,$dflt = null) use (&$data){
      return (($data[$s] ?? null) === null || ($data[$s] == '') || ($data[$s] == []))? $dflt : $data[$s];
    };
    $D = function($s,$dflt = null) use (&$valores_defecto){
      return (($valores_defecto[$s] ?? null) === null || ($valores_defecto[$s] == '') || ($valores_defecto[$s] == []))? $dflt : $valores_defecto[$s];
    };
    $RD = function($s,$dflt = null) use ($R,$D){
      return $R($s,null) ?? $D($s,null) ?? $dflt;
    };
    
    $cotizacion_dolar = bcadd($R(
      'cotizacion_dolar',
      $fecha_cotizacion !== null? ($this->cotizacion($fecha_cotizacion,2) ?? '0.00') : '0.00'
    ),'0',2);//@RETORNADO
    $cotizacion_euro = bcadd($R(
      'cotizacion_euro',
      $fecha_cotizacion !== null? ($this->cotizacion($fecha_cotizacion,2) ?? '0.00') : '0.00'
    ),'0',2);//@RETORNADO
    
    $valor_dolar = '0.00';//@RETORNADO
    $valor_euro  = '0.00';//@RETORNADO
    if($id_casino !== null){
      $valor_dolar = bcadd($RD('valor_dolar',$valor_dolar),'0',2);
      $valor_euro  = bcadd($RD('valor_euro',$valor_euro),'0',2);
    }
    
    $dias_valor = $RD('dias_valor',0);//@RETORNADO
    $valor_diario_dolar = '0.00';//@RETORNADO
    $valor_diario_euro  = '0.00';//@RETORNADO
    if($dias_valor != 0){//No entra si es =0, nulo, o falta
      $valor_diario_dolar = bcdiv(bcmul($cotizacion_dolar,$valor_dolar,self::$max_scale),$dias_valor,2);
      $valor_diario_euro  = bcdiv(bcmul($cotizacion_euro,$valor_euro,self::$max_scale),$dias_valor,2);
    }
    
    $dias_lunes_jueves = 0;//@RETORNADO
    $dias_viernes_sabados = 0;//@RETORNADO
    $dias_domingos = 0;//@RETORNADO
    $dias_todos = 0;//@RETORNADO
    $dias_fijos = $RD('dias_fijos',0);//@RETORNADO
    
    if($año_mes !== null){
      if($fecha_cotizacion === null){
        $año_mes_arr = explode('-',$año_mes);
        if($año_mes_arr[1] < 12){
          $año_mes_arr[1] = str_pad(intval($año_mes_arr[1])+1,2,'0',STR_PAD_LEFT);
        }
        else{
          $año_mes_arr[0] = intval($año_mes_arr[0])+1;
          $año_mes_arr[1] = '01';
        }
        $fecha_cotizacion = implode('-',$año_mes_arr);
      }
      
      $wdmin_wdmax_count_arr = [
        'dias_lunes_jueves'    => [1,4,0],
        'dias_viernes_sabados' => [5,6,0],
        'dias_domingos'        => [0,0,0],
        'dias_todos'           => [0,6,0],
      ];
      
      $calcular_dias_lunes_jueves = $D('calcular_dias_lunes_jueves',true);
      $calcular_dias_viernes_sabados = $D('calcular_dias_viernes_sabados',true);
      $calcular_dias_domingos = $D('calcular_dias_domingos',true);
      $calcular_dias_todos = $D('calcular_dias_todos',true);
      //@SPEED: unset K si no hay que calcular?
      if($calcular_dias_lunes_jueves || $calcular_dias_viernes_sabados || $calcular_dias_domingos || $calcular_dias_todos){
        $año_mes_arr = explode('-',$año_mes);
        $dias_en_el_mes = cal_days_in_month(CAL_GREGORIAN,intval($año_mes_arr[1]),intval($año_mes_arr[0]));
        for($d=1;$d<=$dias_en_el_mes;$d++){
          $año_mes_arr[2] = $d;
          $f = new \DateTime(implode('-',$año_mes_arr));
          $wd = $f->format('w');
          foreach($wdmin_wdmax_count_arr as $k => &$wdmin_wdmax_count){
            if($wd >= $wdmin_wdmax_count[0] && $wd <= $wdmin_wdmax_count[1]){
              $wdmin_wdmax_count[2] = $wdmin_wdmax_count[2] + 1;
            }
          }
        }
      }
      
      $dias_lunes_jueves = $calcular_dias_lunes_jueves? 
        $R('dias_lunes_jueves',$wdmin_wdmax_count_arr['dias_lunes_jueves'][2])
      : 0;
      $dias_viernes_sabados = $calcular_dias_viernes_sabados? 
        $R('dias_viernes_sabados',$wdmin_wdmax_count_arr['dias_viernes_sabados'][2])
      : 0;
      $dias_domingos = $calcular_dias_domingos? 
        $R('dias_domingos',$wdmin_wdmax_count_arr['dias_domingos'][2])
      : 0;
      $dias_todos = $calcular_dias_todos? 
        $R('dias_todos',$wdmin_wdmax_count_arr['dias_todos'][2])
      : 0;
    }
    
    $mesas_lunes_jueves      = $R('mesas_lunes_jueves',0);//@RETORNADO
    $mesas_viernes_sabados   = $R('mesas_viernes_sabados',0);//@RETORNADO
    $mesas_domingos          = $R('mesas_domingos',0);//@RETORNADO
    $mesas_todos             = $R('mesas_todos',0);//@RETORNADO
    $mesas_fijos             = $R('mesas_fijos',0);//@RETORNADO
        
    $mesasdias = $dias_lunes_jueves*$mesas_lunes_jueves
    +$dias_viernes_sabados*$mesas_viernes_sabados
    +$dias_domingos*$mesas_domingos
    +$dias_todos*$mesas_todos
    +$dias_fijos*$mesas_fijos;
    
    $total_dolar = bcmul($valor_diario_dolar,$mesasdias,2);//@RETORNADO
    $total_euro  = bcmul($valor_diario_euro,$mesasdias,2);//@RETORNADO
    $total_devengado = bcadd($total_dolar,$total_euro,2);//@RETORNADO
    $total_pagar = $total_devengado;//@RETORNADO
    
    return compact(
      'tipo','fecha_cotizacion',
      'dias_valor','valor_dolar','valor_euro','cotizacion_dolar','cotizacion_euro','valor_diario_dolar','valor_diario_euro',
      'dias_lunes_jueves','mesas_lunes_jueves','dias_viernes_sabados','mesas_viernes_sabados',
      'dias_domingos','mesas_domingos','dias_todos','mesas_todos','dias_fijos','mesas_fijos',
      'total_dolar','total_euro','total_devengado','total_pagar'
    );
  }
  
  public function mesasAdicionales_recalcular($tipo,$valores_defecto,$data){
    $R = function($s,$dflt = null) use (&$data){
      return (($data[$s] ?? null) === null || ($data[$s] == '') || ($data[$s] == []))? $dflt : $data[$s];
    };
    $D = function($s,$dflt = null) use (&$valores_defecto){
      return (($valores_defecto[$s] ?? null) === null || ($valores_defecto[$s] == '') || ($valores_defecto[$s] == []))? $dflt : $valores_defecto[$s];
    };
    $RD = function($s,$dflt = null) use ($R,$D){
      return $R($s,null) ?? $D($s,null) ?? $dflt;
    };
    
    $valor_mensual = bcadd($RD('valor_mensual','0.00'),'0',2);//@RETORNADO
    $dias_mes      = $RD('dias_mes',0);//@RETORNADO
    $horas_dia     = $RD('horas_dia',0);//@RETORNADO
    $porcentaje    = bcadd($RD('porcentaje','0.0000'),'0',4);//@RETORNADO
    
    $valor_diario = '0.00';//@RETORNADO
    if($dias_mes != 0){
      $valor_diario = bcdiv($valor_mensual,$dias_mes,2);
    }
    
    $valor_hora = '0.00';//@RETORNADO
    if($dias_mes != 0 && $horas_dia != 0){
      $valor_hora = bcdiv($valor_mensual,$horas_dia*$dias_mes,2);
    }
       
    $horas = $R('horas',0);//@RETORNADO
    $mesas = $R('mesas',0);//@RETORNADO
    $total_sin_aplicar_porcentaje = bcmul($valor_hora,($horas*$mesas),2);
    
    $total_devengado = bcdiv(bcmul($total_sin_aplicar_porcentaje,$porcentaje,self::$max_scale),'100',2);//@RETORNADO
    $total_pagar = $total_devengado;//@RETORNADO
    
    return compact('tipo','valor_mensual','dias_mes','valor_diario','horas_dia','valor_hora','horas','mesas','porcentaje','total_devengado','total_pagar');
  }
  
  public function guardar(Request $request){
    return DB::transaction(function() use ($request){
      $created_at = date('Y-m-d h:i:s');
      $id_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      $id_canon_anterior = null;
      {
        $canon_viejos = DB::table('canon')
        ->whereNull('deleted_at')
        ->where('año_mes',$request->año_mes ?? null)
        ->where('id_casino',$request->id_casino ?? null)
        ->orderBy('created_at','desc')
        ->get();
        
        foreach($canon_viejos as $idx => $cv){
          if($idx == 0){//Saco todos los id_archivos para pasarselos a la version de canon nueva
            $id_canon_anterior = $cv->id_canon;
          }
          $this->borrar_arr(['id_canon' => $cv->id_canon],$created_at,$id_usuario);
        }
      }
      
      $datos = $this->recalcular($request);
      
      DB::table('canon')
      ->insert([
        'año_mes' => $datos['año_mes'] ?? null,
        'id_casino' => $datos['id_casino'] ?? null,
        'estado' => 'Generado',
        'bruto_devengado' => $datos['bruto_devengado'] ?? 0,
        'deduccion' => $datos['deduccion'] ?? 0,
        'devengado' => $datos['devengado'] ?? 0,
        'porcentaje_seguridad' => $datos['porcentaje_seguridad'] ?? 0, 
        'fecha_vencimiento' => $datos['fecha_vencimiento'] ?? null,
        'fecha_pago' => $datos['fecha_pago'] ?? null,
        'bruto_pagar' => $datos['bruto_pagar'] ?? 0,
        'interes_mora' => $datos['interes_mora'] ?? 0,
        'mora' => $datos['mora'] ?? 0,
        'a_pagar' => $datos['a_pagar'] ?? 0,
        'pago' => $datos['pago'] ?? 0,
        'diferencia' => $datos['diferencia'] ?? 0,
        'es_antiguo' => ($datos['es_antiguo'] ?? false)? 1 : 0,
        'created_at' => $created_at,
        'created_id_usuario' => $id_usuario,
      ]);
      
      $canon = DB::table('canon')
      ->where('año_mes',$request->año_mes ?? null)
      ->where('id_casino',$request->id_casino ?? null)
      ->whereNull('deleted_at')
      ->first();
      
      foreach(($datos['canon_variable'] ?? []) as $tipo => $datos_cv){
        $datos_cv['id_canon'] = $canon->id_canon;
        $datos_cv['tipo'] = $tipo;
        DB::table('canon_variable')
        ->insert($datos_cv);
      }
      
      foreach(($datos['canon_fijo_mesas'] ?? []) as $tipo => $datos_cfm){
        $datos_cfm['id_canon'] = $canon->id_canon;
        $datos_cfm['tipo'] = $tipo;
        DB::table('canon_fijo_mesas')
        ->insert($datos_cfm);
      }
      
      foreach(($datos['canon_fijo_mesas_adicionales'] ?? []) as $tipo => $datos_cfma){
        $datos_cfma['id_canon'] = $canon->id_canon;
        $datos_cfma['tipo']     = $tipo;
        DB::table('canon_fijo_mesas_adicionales')
        ->insert($datos_cfma);
      }
      
      {
        $archivos_existentes = $id_canon_anterior === null? 
          collect([])
        : DB::table('canon_archivo as ca')
        ->select('ca.descripcion','ca.type','a.*')
        ->join('archivo as a','a.id_archivo','=','ca.id_archivo')
        ->where('id_canon',$id_canon_anterior)
        ->get()
        ->keyBy('id_archivo');
        
        $archivos_enviados = collect($datos['adjuntos'] ?? [])->groupBy('id_archivo');
        $archivos_resultantes = [];
        foreach($archivos_enviados as $id_archivo_e => $archivos_e){
          if($id_archivo_e !== ''){//Es "existente"
            //Se recibio un id archivo que no estaba antes
            if(!$archivos_existentes->has($id_archivo_e)) continue;
            
            $archivo_bd = $archivos_existentes[$id_archivo_e];
            
            $archivo = null;//Por si me mando varios con el mismo id_archivo, busco el que tenga mismo nombre de archivo
            foreach($archivos_e as $ae){
              if($ae['nombre_archivo'] == $archivo_bd->nombre_archivo){
                $archivo = $ae;
                break;
              }
            }
            
            if($archivo === null) continue;//No encontre, lo ignoro
                        
            //El archivo se repite para el nuevo canon pero posiblemente con otra descripcion
            $archivos_resultantes[] = [
              'id_archivo'  => $archivo_bd->id_archivo,
              'id_canon'    => $canon->id_canon,
              'descripcion' => ($archivo['descripcion'] ?? ''),
              'type'        => $archivo_bd->type,
            ];
          }
          else{//Archivos nuevos
            foreach($archivos_e as $a){
              $file=$a['file'] ?? null;
              if($file === null) continue;
              
              $archivo_bd = new Archivo;
              $data = base64_encode(file_get_contents($file->getRealPath()));
              $nombre_archivo = $file->getClientOriginalName();
              $archivo_bd->nombre_archivo = $nombre_archivo;
              $archivo_bd->archivo = $data;
              $archivo_bd->save();
              
              $archivos_resultantes[] = [
                'id_archivo' => $archivo_bd->id_archivo,
                'id_canon' => $canon->id_canon,
                'descripcion' => ($a['descripcion'] ?? ''),
                'type' => $file->getMimeType() ?? 'application/octet-stream'
              ];
            } 
          }
        }
        
        DB::table('canon_archivo')
        ->insert($archivos_resultantes);
      }
      
      return 1;
    });
  }
  
  public function obtener_arr(array $request){
    $ret = (array) DB::table('canon as c')
    ->select('c.*','u.user_name as usuario')
    ->join('usuario as u','u.id_usuario','=','c.created_id_usuario')
    ->where('id_canon',$request['id_canon'])
    ->first();
    $ret = $ret ?? [];
        
    $ret['canon_variable'] = DB::table('canon_variable')
    ->where('id_canon',$request['id_canon'])
    ->get()
    ->keyBy('tipo');
    
    $ret['canon_fijo_mesas'] = DB::table('canon_fijo_mesas')
    ->where('id_canon',$request['id_canon'])
    ->get()
    ->keyBy('tipo');
        
    $ret['canon_fijo_mesas_adicionales'] = DB::table('canon_fijo_mesas_adicionales')
    ->where('id_canon',$request['id_canon'])
    ->get()
    ->keyBy('tipo');
    
    $ret['adjuntos'] = DB::table('canon_archivo as ca')
    ->select('ca.id_canon','ca.descripcion','a.id_archivo','a.nombre_archivo')
    ->join('archivo as a','a.id_archivo','=','ca.id_archivo')
    ->where('ca.id_canon',$request['id_canon'])
    ->orderBy('id_archivo','asc')
    ->get()
    ->transform(function(&$adj){
      $adj->link = '/Ncanon/archivo?id_canon='.urlencode($adj->id_canon)
      .'&nombre_archivo='.urlencode($adj->nombre_archivo);
      return $adj;
    });
    
    $ret['saldo_anterior'] = 0;
    $ret['saldo_anterior'] = $this->calcular_saldo_hasta($ret['año_mes'],$ret['id_casino']);
    $ret['diferencia'] = $ret['pago'] - $ret['a_pagar'];
    $ret['saldo_posterior'] = $ret['saldo_anterior'] + $ret['diferencia'];
    
    return $ret;
  }
  
  public function archivo(Request $request){
    if(($request['id_canon'] ?? null) === null || ($request['nombre_archivo'] ?? null) === null)
      return null;
    
    $a = DB::table('canon_archivo as ca')
    ->select('ca.type','a.*')
    ->join('archivo as a','a.id_archivo','=','ca.id_archivo')
    ->where('ca.id_canon',$request['id_canon'])
    ->where('a.nombre_archivo',$request['nombre_archivo'])
    ->first();
    
    if($a === null) 
      return null;
    
    return \Response::make(
      base64_decode($a->archivo), 
      200, 
      [
        'Content-Type' => $a->type,
        'Content-Disposition' => 'inline; filename="'.$a->nombre_archivo.'"'
      ]
    );
  }
  
  public function obtener(Request $request){
    return $this->obtener_arr($request->all());
  }
  
  public function obtenerConHistorial(Request $request){
    $ultimo = $this->obtener($request);
    $ultimo['historial'] = ($ultimo['id_canon'] ?? null) !== null?
      DB::table('canon')
      ->select('created_at','id_canon')->distinct()
      ->where('año_mes',$ultimo['año_mes'])
      ->where('id_casino',$ultimo['id_casino'])
      ->orderBy('created_at','desc')
      ->get()->map(function($idc,$idc_idx){
        return $this->obtener_arr(['id_canon' => $idc->id_canon]);
      })
    : collect([]);
    return $ultimo;
  }
  
  public function borrar(Request $request,$deleted_at = null,$deleted_id_usuario = null){
    return $this->borrar_arr($request,$deleted_at,$deleted_id_usuario);
  }
  
  public function borrar_arr($arr,$deleted_at = null,$deleted_id_usuario = null){
    return DB::transaction(function() use ($arr,$deleted_at,$deleted_id_usuario){
      $deleted_at = $deleted_at ?? date('Y-m-d h:i:s');
      $deleted_id_usuario = $deleted_id_usuario ?? UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      DB::table('canon')
      ->whereNull('deleted_at')
      ->where('id_canon',$arr['id_canon'] ?? null)
      ->update(compact('deleted_at','deleted_id_usuario'));
      
      return 1;
    });
  }
  
  public function buscar(){
    $ret = DB::table('canon')
    ->select('canon.*','casino.nombre as casino')
    ->join('casino','casino.id_casino','=','canon.id_casino')
    ->whereNull('canon.deleted_at')
    ->orderBy('id_casino','desc')
    ->orderBy('año_mes','desc')
    ->paginate($request->page_size ?? 10);
    //Necesito transformar la data paginada pero si llamo transform() elimina toda la data de paginado
    $ret2 = $ret->toArray();
    
    //@HACK: asume que esta ordenado por año_mes descendiente
    //cambiar el algoritmo si se da la posibilidiad de reordenar
    $saldo_anterior = [];
    $ret2['data'] = $ret->reverse()->transform(function(&$c) use (&$saldo_anterior){
      if(($saldo_anterior[$c->id_casino] ?? null) === null){
        $saldo_anterior[$c->id_casino] = $this->calcular_saldo_hasta($c->año_mes,$c->id_casino);
      }
      $c->saldo_posterior = $saldo_anterior[$c->id_casino]+$c->diferencia;
      $saldo_anterior[$c->id_casino] = $c->saldo_posterior;
      return $c;
    })->reverse();
    
    return $ret2;
  }
  
  public function cotizacion($fecha_cotizacion,$id_tipo_moneda){
    if(empty($fecha_cotizacion) || empty($id_tipo_moneda)) return null;
    return null;//@TODO
  }
  
  private function valorPorDefecto($k){
    $db = DB::table('canon_valores_por_defecto')
    ->whereNull('deleted_at')
    ->where('campo',$k)
    ->first();
        
    $val = is_null($db)? '{}' : preg_replace('/(\r\n|\n|\s\s+)/i','',$db->valor);
    
    return json_decode($val,true);
  }
    
  public function valoresPorDefecto(Request $request){
    return DB::table('canon_valores_por_defecto')
    ->whereNull('deleted_at')
    ->orderBy('campo','asc')
    ->paginate($request->page_size);
  }
  
  public function valoresPorDefecto_ingresar(Request $request){
    return DB::transaction(function() use ($request){
      $created_at = date('Y-m-d h:i:s');
      $id_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      $vals_viejos = DB::table('canon_valores_por_defecto')
      ->whereNull('deleted_at')
      ->where('campo',$request->campo ?? '')->get();
      foreach($vals_viejos as $v){
        $this->valoresPorDefecto_borrar_arr(['id_canon_valor_por_defecto' => $v->id_canon_valor_por_defecto],$created_at,$id_usuario);
      }
      
      DB::table('canon_valores_por_defecto')
      ->insert([
        'campo' => $request->campo ?? '',
        'valor' => $request->valor ?? '',
        'created_at' => $created_at,
        'deleted_at' => null,
        'created_id_usuario' => $id_usuario,
        'deleted_id_usuario' => null,
      ]);
      
      return 1;
    });
  }
  
  public function cambiarEstado(Request $request){
    return DB::transaction(function() use ($request){
      $updateado = DB::table('canon')
      ->whereNull('deleted_at')
      ->where('id_canon',$request->id_canon)
      ->update(['estado' => $request->estado]) == 1;
      
      $estado = 200;
      $ret = ['id_canon' => $request->id_canon,'estado' => $request->estado,'mensaje' => ''];
      if($updateado != 1){
        $estado = 422;
        $ret['mensaje'] = 'Error, canon no encontrado';
      }
      return $ret;
    });
  }
  
  public function valoresPorDefecto_borrar(Request $request,$deleted_at = null,$deleted_id_usuario = null){
    return $this->valoresPorDefecto_borrar_arr($request,$deleted_at,$deleted_id_usuario);
  }
  
  public function valoresPorDefecto_borrar_arr($arr,$deleted_at = null,$deleted_id_usuario = null){
    return DB::transaction(function() use ($arr,$deleted_at,$deleted_id_usuario){
      $deleted_at = $deleted_at ?? date('Y-m-d h:i:s');
      $deleted_id_usuario = $deleted_id_usuario ?? UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      DB::table('canon_valores_por_defecto')
      ->whereNull('deleted_at')
      ->where('id_canon_valor_por_defecto',$arr['id_canon_valor_por_defecto'] ?? null)
      ->update(compact('deleted_at','deleted_id_usuario'));
      
      return 1;
    });
  }
}
