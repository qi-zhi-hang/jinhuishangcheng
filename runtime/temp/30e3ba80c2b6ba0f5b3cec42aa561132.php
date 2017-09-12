<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:39:"./template/mobile/new/user\lottery.html";i:1503049148;}*/ ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<title>抽奖送积分</title>
	<link rel="stylesheet" type="text/css" href="__STATIC__/lotter/css/public/reset.css">
	<link rel="stylesheet" type="text/css" href="__STATIC__/lotter/css/public/font-awesome.css">
	<link rel="stylesheet" type="text/css" href="__STATIC__/lotter/css/public/function.css">
	<link rel="stylesheet" type="text/css" href="__STATIC__/lotter/css/style.css">

	<script type="text/javascript" src="__STATIC__/lotter/js/model/jquery-1.8.0.min.js"></script>
	<script type="text/javascript" src="__STATIC__/lotter/js/model/jquery.SuperSlide.2.1.1.source.js"></script>
	<script type="text/javascript" src="__STATIC__/lotter/js/model/html5.js"></script>
	<script type="text/javascript" src="__STATIC__/lotter/js/public.js"></script>
	<script type="text/javascript" src="__STATIC__/lotter/js/app.js"></script> 
<style type="text/css">
	
	#fanhui{

		margin: 30px;
		margin-top: 30px;
		font-family: Droid Sans;
		color: #ffb820;


	}

</style>
</head>
<body class="g-body-in">
	<div class="g-wrap">
		<section class="h15"><div><a  id="fanhui" href="<?php echo U('mobile/Index/index'); ?>">返回</a></div></section>
		<section class="main-sec pt5 main-wheel">
			<div class="big-border">
				<div class="small-border g9">

					<div class="shan">
						<span>一积分</span>
						<img src="__STATIC__/lotter/images/monery.png" width="30%">
					</div>

					<div class="shan">
						<span>谢谢惠顾</span>
						<img src="__STATIC__/lotter/images/monery.png" width="30%">
					</div>

					<div class="shan">
						<span>谢谢惠顾</span>
						<img src="__STATIC__/lotter/images/monery.png" width="30%">
					</div>

					<div class="shan">
						<span>四积分</span>
						<img src="__STATIC__/lotter/images/monery.png" width="30%">
					</div>
					
					<div class="shan">
						<span>谢谢惠顾</span>
						<img src="__STATIC__/lotter/images/monery.png" width="30%">
					</div>

					<div class="shan">
						<span>五积分</span>
						<img src="__STATIC__/lotter/images/monery.png" width="30%">
					</div>

					<div class="shan">
						<span>谢谢惠顾</span>
						<img src="__STATIC__/lotter/images/monery.png" width="30%">
					</div>

					<div class="shan">
						<span>二积分</span>
						<img src="__STATIC__/lotter/images/monery.png" width="30%">
					</div>

					<div class="shan">
						<span>谢谢惠顾</span>
						<img src="__STATIC__/lotter/images/monery.png" width="30%">
					</div>

					<img src="__STATIC__/lotter/images/middle.png" width="50%" class="middle">
				</div>
			</div>
		</section>

		<section class="main-sec">
			<div class="g-num">欢迎来到金辉商城</div>
		</section>

		<section class="main-sec loptop">
			<div class="m-title"><h3>有谁在抢</h3></div>
			<dl class="peolist">
				<?php if(is_array($data) || $data instanceof \think\Collection): $i = 0; $__LIST__ = $data;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?>
				<dd>
					<img src="__STATIC__/lotter/images/facepic.jpg" width="20%">
					<div class="right">
						<span><h2><?php echo suohao($vo['mobile']); ?></h2><em><?php echo date("Y-m-d H:i:s",$vo['change_time']); ?></em></span>
						<p>获取<?php echo $vo['pay_points']; ?>积分</p>
					</div>
				</dd>
				<?php endforeach; endif; else: echo "" ;endif; ?>
				
			</dl>
			
		</section>
		<?php 
			function suohao($phone){
				 $p = substr($phone,0,3)."****".substr($phone,7,4);
				 return $p;
				}

		?>
		<script type="text/javascript">
			jQuery(".loptop").slide( {mainCell:"dl",autoPage:true,effect:'topLoop',autoPlay:true,scroll:3,vis:3,easing:'swing',delayTime:500, interTime:3000, pnLoop:true});

		</script>

		<section class="main-sec">
			<div class="m-title"><h3>活动说明</h3></div>
			<div class="einfo">
				<p>活动时间：7月1日-10月30日</p>
				<p>只要有足够的积分，可以无限制抽奖</p>
				<p>每日邀请好友参与可获1次抽奖机会</p>
			</div>
		</section>

		<section class="main-sec">
			<footer>金辉商城,提供技术支持</footer>
		</section>
	</div>

	<div class="dialog gz">
		<div class="d-main">
			<p>请进入"镇江微生活"公众号立即参与</p>
			<div class="btn-w">
				<a class="btn" href="#">进入</a>
				<a class="btn false" href="javascript:void(0);">取消</a>
			</div>
		</div>
	</div>

	<div class="dialog info">
		<div class="d-main">
			<p>您的积分余额不足，请充值</p>
			<p><a class="btn btn-lang close" href="javascript:;">确定</a></p>
		</div>
	</div>
	
	
	<div class="dialog theForm">
		<div class="d-main">
			<p id="my_d_main"></p>
			<div class="btn-w">
				<a class="btn btn-lang close" href="javascript:;">确定</a>
			</div>
		</div>
	</div>
	
	
	<div class="dialog again">
		<div class="d-main">
			<p>再来一次</p>
			<div class="btn-w">
				<a class="btn close" href="javascript:;">再来一次</a>
				<a class="btn false" href="javascript:;">取消</a>
			</div>
		</div>
	</div>


</body>

</script>
<script type="text/javascript">

	var valueJson = {
		'wheelBody' : $('.big-border'), //转盘主体
		'wheelSmall' : $('.small-border'), //转盘内部
		'starsNum' : 16, //转盘边缘小黄点个数

		'starsPostion' : [[50, 0.5], [70, 6], [84.5, 18], [92.5, 32], [95.5, 50], [91, 68], [81.5, 81.5], [68, 91], [50, 95.5], [32, 92.5], [16, 83], [6, 70], [0.5, 50], [3.5, 32], [14, 15], [27, 5.5]], //小圆点坐标
		'actionRan' : 7200, //转盘转动弧度
		'theOnce' : 0, //初始化转盘第一个
		
		'startBtn' : $('.middle'), //开始按钮

		//需要后台传值的参数
		'clickAjaxUrl' : "<?php echo U('user/ajaxLottery'); ?>", //点击抽奖获取信息的交互的ajax
		'is_gz' : 1, //是否开启关注 1开 2 关
		'is_follow' : 1 //是否关注

 		};


	
	indexApp.init(valueJson).wheelStart(); //应用开始
</script>
</html>