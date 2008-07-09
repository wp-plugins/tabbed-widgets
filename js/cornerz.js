/**
* Cornerz 0.4 - Bullet Proof Corners
* Jonah Fox (jonah@parkerfox.co.uk) 2008
* 
* Usage: $('.myclass').curve(options)
* options is a hash with the following parameters. Bracketed is the default
*   radius (10)
*   borderWidth (read from BorderTopWidth or 0)
*   background ("white"). Note that this is not calculated from the HTML as it is expensive
*   borderColor (read from BorderTopColor)
*   corners ("tl br tr bl"). Specify which borders
*/
    
;(function($){

  if($.browser.msie && document.namespaces["v"] == null) {
    document.namespaces.add("v", "urn:schemas-microsoft-com:vml");
    var ss = document.createStyleSheet().owningElement;
    ss.styleSheet.cssText = "v\\:*{behavior:url(#default#VML);}"
  }

  $.fn.cornerz = function(options){
    
    function canvasCorner(t,l, r,bw,bc,bg){
	    var sa,ea,cw,sx,sy,x,y, p = 1.57, css="position:absolute;"
	    
	    
      if(t) 
        {sa=-p; sy=r; y=0; css+="top:-"+bw+"px;";  }
      else 
        {sa=p; sy=0; y=r; css+="bottom:"+(-bw)+"px;"; }
      if(l) 
        {ea=p*2; sx=r; x=0;	css+="left:-"+bw+"px;"}
      else 
        {ea=0; sx=0; x=r; css+="right:"+(-bw)+"px;";	}
		
	    var canvas=$("<canvas width="+r+"px height="+ r +"px style='" + css+"' ></canvas>")
	    var ctx=canvas[0].getContext('2d')
	    ctx.beginPath();
	    ctx.lineWidth=bw*2;	
	    ctx.arc(sx,sy,r,sa,ea,!(t^l));
	    ctx.strokeStyle=bc
	    ctx.stroke()
	    ctx.lineWidth = 0
	    ctx.lineTo(x,y)
	    ctx.fillStyle=bg
	    ctx.fill()
	    return canvas
    }

    function canvasCorners(corners, r, bw,bc,bg,w,h,f) {
	    var hh = $("<div style='display: inherit' />") // trying out style='float:left' 
	    $.each(corners.split(" "), function() {
	      hh.append(canvasCorner(this[0]=="t",this[1]=="l", r,bw,bc,bg,w,h,f))
	    })
	    return hh
    }

    function vmlCurve(r,b,c,m,ml,mt, right_fix) {
        var l = m-ml-right_fix
        var t = m-mt
        return "<v:arc filled='False' strokeweight='"+b+"px' strokecolor='"+c+"' startangle='0' endangle='361' style=' top:" + t +"px;left: "+ l + ";width:" + r+ "px; height:" + r+ "px' />"
    }
    

    function vmlCorners(corners, r, bw, bc, bg, w, h) {
      var html ="<div style='text-align:left; '>"
      $.each($.trim(corners).split(" "), function() {
        var css,ml=1,mt=1,right_fix=0
        
        if(this.charAt(0)=="t") {
          css="top:-"+bw+"px;"
        }
        else {
          css= "top:"+(h-r-bw)+"px;"
          mt=r;
        }
        if(this.charAt(1)=="l")
          css+="left:-"+bw+"px;"
        else {
          css +="left:"+(-bw+w-r)+"px; " 
           ml=r
        }

        html+="<div style='"+css+"; position: absolute; overflow:hidden; width:"+ r +"px; height: " + r + "px;'>"
        html+= "<v:group  style='width:1000px;height:1000px;position:absolute;' coordsize='1000,1000' >"
        html+= vmlCurve(r*3,r+bw,bg, -r/2,ml,mt,right_fix) 
        if(bw>0)
          html+= vmlCurve(r*2-bw,bw,bc, bw/2,ml,mt,right_fix)
        html+="</v:group>"
        html+= "</div>" 
      })
      html += "</div>"
      return html
    }

    var settings = {
      corners : "tl tr bl br",
      radius : 10,
      background: "white",
      borderWidth: 0
    }
              
    $.extend(settings, options || {});
    
    var incrementProperty = function(elem, prop, x) {
      
      var y = parseInt(elem.css(prop)) || 0 
      elem.css(prop, x+y)
    }
    
    return this.each(function() {
      
      var $$ = $(this)
      var r = settings.radius*1.0
      var bw = (settings.borderWidth || parseInt($$.css("borderTopWidth")) || 0)*1.0
      var bg = settings.background
      var bc = settings.borderColor
      bc = bc || ( bw > 0 ? $$.css("borderTopColor") : bg)
            
      var cs = settings.corners
      if($.browser.msie) {//need to use innerHTML rather than jQuery
        h = vmlCorners(cs,r,bw,bc,bg, $$.outerWidth(), $$.outerHeight())     // can't use relative positioning with IE as it can't handle right/bottom for odd (2n+1) dimensions
        this.innerHTML += h
        
      }
      else  //canvasCorners returns a DOM element
        $$.append(canvasCorners(cs,r,bw,bc,bg))
        
      if(this.style.position != "absolute")
        this.style.position = "relative"
        this.style.zoom = 1 // give it a layout in IE
      }
      
    )  

  }
})(jQuery);

