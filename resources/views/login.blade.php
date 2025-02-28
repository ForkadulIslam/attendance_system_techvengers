    <html>
    <head>
        <title>
            TechVengers Attendance
        </title>
             {!! HTML::script('js/jquery-1.9.0.min.js') !!}
             {!! HTML::script('js/bootstrap3/js/bootstrap.js') !!}
             {!! HTML::script('js/bootstrap3/js/jquery.pnotify.js') !!}
             {!! HTML::script('js/bootstrap3/js/jquery.nicescroll.min.js') !!}
            {!! HTML::style('js/bootstrap3/css/jquery.pnotify.default.css') !!}
            {!! HTML::style('js/bootstrap3/css/jquery.pnotify.default.icons.css') !!}
            {!! HTML::style('js/bootstrap3/css/bootstrap.min.css') !!}
            {!! HTML::style('js/bootstrap3/css/font-awesome.min.css') !!}
            {!! HTML::style('js/bootstrap3/css/main.css') !!}
        <script>
            (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
                (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
                    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
            })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

            ga('create', 'UA-60038966-1', 'auto');
            ga('send', 'pageview');

        </script>
        <script type="text/javascript" language="javascript">
            function resultDelete()
            {
                var chk=confirm("Are you sure to delete this?");
                if(chk)
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }
        </script>
    </head>
    <body>
        <div class="rc">
            <div class="container main">
                    @yield('content')
            </div>
        </div>
        <section id="bottom" style="margin-top: 50px">
            <div class="container">
                <div class="bottom">
                    <div class="row">
                        <div class="col-xs-12 col-sm-4 col-md-3 margin-btm">
                            <!--                            <h3>Umbro</h3>
                                                        <ul>
                                                            <li>Our Themes</li>
                                                            <li>About Us</li>
                                                            <li>Our Blog</li>
                                                        </ul>-->
                        </div>
                        <div class="col-xs-12 col-sm-4 col-md-3 margin-btm">
                            <!--<h3>Support</h3>-->
                        </div>
                        <div class="col-xs-12 col-sm-4 col-md-3 margin-btm">
                            <!--<h3>Partners</h3>-->
                        </div>
                        <div class="col-xs-12 col-sm-4 col-md-3 margin-btm">
                            <!--<h3>Newsletter</h3>-->
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <footer id="footer">
            <div class="container">
                <div class="footer">
                    <div class="row">
                        <div class="col-md-12">
                            <span>&copy; 2025 <a href="http://www.kingpabel.com">Fuad</a>. All Rights Reserved.</span>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </body>
    </html>