var compDiv;
var arrPageScroll;
var arrPageSizes=new Array();

function openPopDiv(divId)
{
	compDiv=divId;
	centerPopup(divId);
	loadPopup(divId);
}
function closePopDiv(divId)
{
    compDiv=divId;
    disablePopup(divId)
	$('#popupEdit').hide();
}
function loadPopup(popDiv)
{
    $("#fade").show();
    $("div#"+popDiv).show()
}
function disablePopup(popDiv)
{
    $("#fade").hide();
    $("div#"+popDiv).hide()
}
function centerPopup(popDiv)
{
    var windowWidth=$(document).width();
    var windowHeight=$(document).height();
    var popupHeight=$("div#"+popDiv).height();
    var popupWidth=$("div#"+popDiv).width();
    arrPageScroll=___getPageScroll();
    arrPageSizes=___getPageSize();
    $("div#"+popDiv).css({"position":"absolute","top":(arrPageSizes[3]/ 2 - $("#" + compDiv).height() /2+getScrollTop())+"px","left":windowWidth/2-popupWidth/2,"z-Index":1002});
   $("#fade").css({"height":windowHeight,opacity:0.7,"width":windowWidth,"backgroundColor":"black","position":"absolute"});
	
}

$(document).ready(function()
{
    $("#fade").click(function(){closePopDiv('pop-up')});
    $(document).keypress(function(e){
    if(e.keyCode==27){closePopDiv(compDiv)}});
    $(window).scroll(function(){$("#"+compDiv).stop().animate({"top":(arrPageSizes[3]/ 2 - $("#" + compDiv).height() /2+getScrollTop())+"px",opacity:1.0},500)})
});

function ___getPageSize(){var xScroll,yScroll;if(window.innerHeight&&window.scrollMaxY){xScroll=window.innerWidth+window.scrollMaxX;yScroll=window.innerHeight+window.scrollMaxY}else if(document.body.scrollHeight>document.body.offsetHeight){xScroll=document.body.scrollWidth;yScroll=document.body.scrollHeight}else{xScroll=document.body.offsetWidth;yScroll=document.body.offsetHeight}var windowWidth,windowHeight;if(self.innerHeight){if(document.documentElement.clientWidth){windowWidth=document.documentElement.clientWidth}else{windowWidth=self.innerWidth}windowHeight=self.innerHeight}else if(document.documentElement&&document.documentElement.clientHeight){windowWidth=document.documentElement.clientWidth;windowHeight=document.documentElement.clientHeight}else if(document.body){windowWidth=document.body.clientWidth;windowHeight=document.body.clientHeight}if(yScroll<windowHeight){pageHeight=windowHeight}else{pageHeight=yScroll}if(xScroll<windowWidth){pageWidth=xScroll}else{pageWidth=windowWidth}arrayPageSize=new Array(pageWidth,pageHeight,windowWidth,windowHeight);return arrayPageSize};
function ___getPageScroll(){var xScroll,yScroll;if(self.pageYOffset){yScroll=self.pageYOffset;xScroll=self.pageXOffset}else if(document.documentElement&&document.documentElement.scrollTop){yScroll=document.documentElement.scrollTop;xScroll=document.documentElement.scrollLeft}else if(document.body){yScroll=document.body.scrollTop;xScroll=document.body.scrollLeft}arrayPageScroll=new Array(xScroll,yScroll);return arrayPageScroll};

function getScrollTop()
{
    var ScrollTop=document.body.scrollTop;
    if(ScrollTop==0)
    {
        if(window.pageYOffset)
        ScrollTop=window.pageYOffset;
        else 
        ScrollTop=(document.body.parentElement)?document.body.parentElement.scrollTop:0;
     }
        return ScrollTop;
}