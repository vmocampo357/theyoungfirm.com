$(document).ready(function(){
    console.log('Maritime Law Firm JS Active ...');

    /**
     * Fix for the white page scroll
     *
     */
    setTimeout(function(){
        console.log('Should scroll a little bit here.');
        window.scrollTo(0,2);
    }, 25);

    /**
     * Captcha Injection
     *
     * For any Captcha Submit, we'll add a captcha that needs to pass submission
     * before continuing.
     *
     */
    if($(".infusion-form").length > 0) {

        console.log("New updates");

        $(".infusion-form").each(function(i, e) {
            /*
             Find the form, we'll submit it
             */
            var form = $(this);//$(this).parent().parent();

            /*
             Swap out the actions on the forms now
             */
            if (typeof form.attr("data-alt") != "undefined" && form.attr("data-alt")) {
                console.log(form.attr("data-alt"));
                form.attr("action", $(this).attr("data-alt"));    
            }
        });

        // $(document).off("submit",".infusion-form").on("submit",".infusion-form", function (e) {
        //
        //     /*
        //      Find the form, we'll submit it
        //      */
        //     var form = $(this);//$(this).parent().parent();
        //
        //     console.log(form.attr("data-alt"));
        //
        //     /*
        //      Swap out the actions on the forms now
        //      */
        //     form.attr("action", $(this).attr("data-alt"));
        //
        //     // /*
        //     //  Now, get the field that should have the answer
        //     //  */
        //     // var answerField = form.find('input[name=infusion-middle-name]');
        //     //
        //     // /*
        //     // Also, we check to make sure honeypot field isn't set
        //     //  */
        //     // var honeypotField = form.find("input[name=infusion-address-2]");
        //     //
        //     // if(answerField.length > 0 && honeypotField.val() == "") {
        //     //
        //     //     /*
        //     //      Get the submitted answer, and compare it to the correct answer
        //     //      */
        //     //     var answer = parseInt(answerField.val());
        //     //     var correctAnswer = parseInt(form.find(".infusion-recaptcha").attr("data-answer"));
        //     //
        //     //     if(answer !== correctAnswer) {
        //     //         alert('Please enter the correct solution!');
        //     //         return false;
        //     //     } else {
        //     //         return true;
        //     //     }
        //     // } else {
        //     //     alert('There was an issue submitting the form!');
        //     //     return false;
        //     // }
        // });

        // /*
        //  First, let's create an HTML string of our captcha field
        //  */
        // var captchaFieldHtmlTemplate = "<p>Please enter the solution for [$a+$b]:</p>" +
        //     "<p><input name='infusion-middle-name' type='text' class='infusionsoft-number infusion-field-input' /></p>";
        //
        // var honeypotFieldHtmlTemplate = "<div style='position:fixed;top:-999999px;left:-99999px;z-index:1'>"+
        //                                 "<input type='text' name='infusion-address-2' value='' />"+
        //                                 "</div>";

        /*
         Now, go through every infusion submit field
         */
        // $(".infusion-submit").each(function(i, e) {
        //
        //     console.log("INFUSIONCAPTCHA: Submission field found, adding HTML");
        //
        //     /*
        //      Generate a random a, b and answer
        //      */
        //     var answer = 0;
        //     var captchaFieldHtml = captchaFieldHtmlTemplate;
        //     var $a = Math.floor(Math.random() * (+10 - +1)) + +1;
        //     var $b = Math.floor(Math.random() * (+10 - +1)) + +1;
        //     answer = $a + $b;
        //
        //     /*
        //      Make the correct form fields
        //      */
        //     captchaFieldHtml = captchaFieldHtmlTemplate.replace("$a", $a);
        //     captchaFieldHtml = captchaFieldHtml.replace("$b", $b);
        //
        //     /*
        //      Prepare the div element
        //      */
        //     var div = $("<div></div>");
        //     div.html(captchaFieldHtml + honeypotFieldHtmlTemplate);
        //
        //     /*
        //      Insert the captcha field before any other elements
        //      */
        //     $(e).prepend(div);
        //
        //     /*
        //      Get the first submit field in this group
        //      */
        //     var submitFields = $(e).children(".infusion-recaptcha");
        //
        //     /*
        //      On each submission field, we'll put the correct answer -- then later on submission we check that
        //      */
        //     if(submitFields.length > 0) {
        //
        //         /*
        //          Set the 'data' field
        //          */
        //         var submitField = $(submitFields[0]);
        //
        //         submitField.attr("data-answer", answer);
        //
        //     }
        // });
    }

    /**
     * If the homepage links are visible
     */
    if($(".homepage-icon-link").length > 0)
    {
        console.log("INITIALIZING homepage links");
        $(document).on('click','.homepage-icon-link',function(e){
            var parent = $(this).parent();
            var links = parent.find(".btn");
            if(links.length > 0)
            {
                var $link = $(links[0]);
                console.log($link.attr("href"));
                window.location.href = $link.attr("href");
            }
        });
    }

    /**
     * This will make every anchor link be a little bit offset for better targeting
     */
    $('a[href^="#"]').click(function(e){
        e.preventDefault();
        var element = $($(this).attr("href"));
        console.log(element);
        if(element.length > 0)
        {
            $(window).scrollTo(element.offset().top - 110);
        }
    });

    /**
     * This will open the page form on any 'single' page that includes a contact form
     * @returns {boolean}
     */
    function showPageForm()
    {
        var element = $("#jal-this-page-comment-form");
        var element_state = element.attr('data-state');
        if(element_state == "closed"){
            /*element.slideDown();*/
            element.show();
            element.attr('data-state',"opened");
            $(window).scrollTo($("#jal-this-page-comment-form").offset().top-110,"slow");
            return false;
        }
    }

    /**
     * This will hide the page form on any 'single' page that includes a contact form
     */
    function hidePageForm()
    {
        var element = $("#jal-this-page-comment-form");
        var element_state = element.attr('data-state');
        if(element_state == "opened"){
            /*element.slideUp();*/
            element.hide();
            setTimeout(function(){
                $(window).scrollTo( $(window).scrollTop()-1 );
            },0);
            element.attr('data-state',"closed");
        }
    }

    /**
     * This will toggle the page form on any 'single' page that includes a contact form
     */
    function togglePageForm()
    {
        var element = $("#jal-this-page-comment-form");
        var element_state = element.attr('data-state');
        if(element_state == "opened"){
            hidePageForm();
        }else{
            showPageForm();
        }
    }

    /**
     * This will show the MegaMenu for the given menu-parent ID
     * @param id
     */
    function showMegaMenu(id)
    {
        $(".mega-menu-content").hide();
        $("#mega-menu-content-id-"+id).show();
        $("#jal-div-element-mega-menu").show();
    }

    /**
     * This will hide the active mega menu
     */
    function hideMegaMenu()
    {
        $("#jal-div-element-mega-menu").hide();
    }

    /**
     * This will remove the 'hover' from all active list items
     */
    function resetHoverActions()
    {
        $("#jal-ul-element-main-menu li").removeClass('hover');
    }

    /**
     * This method will go through each item that has children and add a Karet for later use.
     */
    function initializeResponsiveMenu()
    {
        var collection = $(".jal-responsive-menu").find(".menu-item-has-children");
        if(collection.length > 0)
        {
            for(var i=0; i<collection.length; i++)
            {
                var el = $(collection[i]);
                el.append("<div data-state='closed' class='karet'><span class='icon fa fa-caret-right'></span></div>");
            }
        }
        $(document).on("click",".jal-responsive-menu .karet",function(e){
            var state = $(this).attr("data-state");
            var icon = $(this).find(".icon");
            var parent = $(this).parent();
            if(state == "closed")
            {
                // need to open
                icon.removeClass("fa-caret-right");
                icon.addClass("fa-caret-down");
                $(this).attr("data-state","open");
                parent.children("ul").show();
            }else{
                // need to close
                icon.removeClass("fa-caret-down");
                icon.addClass("fa-caret-right");
                $(this).attr("data-state","closed");
                parent.children("ul").hide();
            }
            e.stopPropagation();
        });
    }

    initializeResponsiveMenu();

    /**
     * This initial run will start the menu off as 'hidden'
     */
    hideMegaMenu();

    /**
     * DOCUMENT CLICK HANDLERS
     * ---
     * This section carries all event handlers (namely, clicks) sitewide
     *
     */

    function toggleResponsiveMenu()
    {
        var menu = $(".jal-responsive-menu");
        var toggler = $(".trigger-responsive-menu-toggle");
        if(menu.hasClass("jal-responsive-menu-open"))
        {
            menu.removeClass("jal-responsive-menu-open");
            toggler.removeClass("hidden");
        }else{
            menu.addClass("jal-responsive-menu-open");
            toggler.addClass("hidden");
        }
    }

    /**
     * This will open the responsive menu, when enabled
     */
    $(document).on("click",".trigger-responsive-menu-toggle",function(){
        toggleResponsiveMenu();
    });
    $(document).on("click",".jal-responsive-close-box a",function(e){
        toggleResponsiveMenu();
    });

    /**
     * This will toggle the mobile search form
     */
    $(document).on("click",".toggle-mobile-search-form",function(e){
        $("#responsive-search-box").toggle();
    });

    /**
     * This will open any children that the main menu may have
     */
    $(document).on("click",".menu-item-has-children",function(e){
        /*e.stopPropagation();
        $(this).children("ul").toggle();*/
    });

    /**
     * When we mouseover the mega menu, we want to show the correct menu
     */
    $(document).on('mouseover','#jal-div-element-mega-menu',function(){
        console.log('Inside menu!');
        showMegaMenu( last_menu_id );
        $(this).addClass('hover');
    });

    /**
     * On the main menu, we want to show the mega menu when we hover
     */
    $(document).on('mouseover','#jal-ul-element-main-menu li.jal-mega-li',function(){
        last_menu_id = $(this).attr("data-mega-menu-id");
        showMegaMenu( last_menu_id );
        resetHoverActions();
        $(this).addClass('hover');
    });

    /**
     * When our mouse leaves the mega menu, we want it to close
     */
    $(document).on('mouseleave','#jal-div-element-mega-menu',function(){
        console.log('Outside menu!');
        hideMegaMenu();
        resetHoverActions();
        $(this).removeClass('hover');
    });-

    /**
    * When we no longer hover over the top menu, we want to close the mega menu
    */
    $(document).on('mouseout','#jal-ul-element-main-menu li',function(){
        hideMegaMenu();
        _this = $(this);
        setTimeout(function(){
            if(!$("#jal-div-element-mega-menu").hasClass('hover')){
                _this.removeClass('hover');
            }
        },100);
    });

    // Get the navbar
    sticky_header = $("#jal-row-element-header-container").sticky({topSpacing:0});

    $(window).resize(function(){
        setTimeout(function(){
            $("#jal-row-element-header-container").sticky({topSpacing:0});
        },1);
    });

    // Toggling this page form
    if($(".trigger-this-page-comment-form-toggle").length > 0)
    {
        togglePageForm();
        $(".trigger-this-page-comment-form-toggle").click(function(){
            togglePageForm();
        });
    }

    /**
     * If our page has the compass up, we want to have it appear on scroll
     */
    if( $("#jal-compass-up").length > 0 )
    {
        /*$(window).on("scroll", function(e){
            if( $(window).scrollTop() > 500 ){
                $("#jal-compass-up").fadeIn();
            }else{
                $("#jal-compass-up").fadeOut();
            }
            $(document).on("click","#jal-compass-up",function(){
                $(window).scrollTo(0,0);
            });
        });*/
    }

    /**
     * If our page has a sticky bar, we want it to show up and scroll with us
     */
    /*if( $("#jal-any-sticky-bar").length > 0 )
    {
        $("#jal-any-sticky-bar").stick_in_parent({
            parent: "#jal-row-element-primary-body",
            offset_top:120,
            bottoming:true
        });
    }*/

    if( $(".faq-floating-sticky-bar").length > 0 )
    {
        var window_position = $(window).scrollTop();
        var faq_links_position = $("#faq-main-links").offset().top;

        if(window_position < faq_links_position)
        {
            $(".faq-floating-sticky-bar").hide();
        }

        $(window).on("scroll", function(e){
            var window_position = $(window).scrollTop();
            var faq_links_position = $("#faq-main-links").offset().top;
            if(window_position < faq_links_position)
            {
                $(".faq-floating-sticky-bar").hide();
            }else{
                $(".faq-floating-sticky-bar").show();
            }
        });
    }

    if( $("#jal-ul-testimonial-video-library").length > 0 )
    {
        $("#jal-ul-testimonial-video-library").slick({
            infinite:true,
            slidesToShow:2,
            slidesToScroll:1,
            prevArrow:'<button type="button" class="slick-prev-jal"><i class="fa fa-arrow-circle-left"></i></button>',
            nextArrow:'<button type="button" class="slick-next-jal"><i class="fa fa-arrow-circle-right"></i></button>',
            responsive:[
                {
                    breakpoint:1160,
                    settings:{
                        slidesToShow: 2
                    }
                },
                {
                    breakpoint:860,
                    settings:{
                        slidesToShow:2
                    }
                },
                {
                    breakpoint:800,
                    settings:{
                        slidesToShow:1
                    }
                }
            ]
        });
    }

    /**
     * If our page has any video slideshow, we add the Slick settings
     */
    if( $(".jal-ul-video-slick").length > 0)
        $(".jal-ul-video-slick").slick({
            infinite: true,
            slidesToShow: 3,
            slidesToScroll: 1,
            prevArrow:'<button type="button" class="slick-prev-jal"><i class="fa fa-arrow-circle-left"></i></button>',
            nextArrow:'<button type="button" class="slick-next-jal"><i class="fa fa-arrow-circle-right"></i></button>',
            responsive:[
                {
                    breakpoint:1160,
                    settings:{
                        slidesToShow: 2
                    }
                },
                {
                    breakpoint:860,
                    settings:{
                        slidesToShow:2
                    }
                },
                {
                    breakpoint:800,
                    settings:{
                        slidesToShow:1
                    }
                }
            ]
        });

    /**
     * If our page has the Navigation Tabs, we activate here
     */
    if( $(".nav-tabs").length > 0 )
    {
        $(document).on('click','.nav-tabs li',function(){
            $(".jal-any-tab-content").removeClass('open');
            var target = $(this).attr("data-target-tab");
            $(".jal-any-tab-content[data-tab-name='"+target+"']").addClass('open');
            $(".nav-tabs li").removeClass('active');
            $(this).addClass('active');
        });
        $(".nav-tabs li:first-child").trigger('click');
    }

    /**
     * If our page has the FAQ section, we activate it here
     */
    if( $(".faq-any-ul").length > 0 )
    {
        $(document).on('click','.faq-any-ul li',function(){
            $('.faq-any-ul li').removeClass('open');
            $(".faq-any-ul li").find("i").removeClass('fa-caret-up');
            $(this).addClass('open');
            $(this).find('i').addClass('fa-caret-up');
        });
    }

    /**
     * If our page has the Chained Quiz loaded
     */
    if( $("#jal-chained-quiz-custom").length > 0 )
    {
        window.quiz = new JalChainedQuiz();
        quiz.setTarget("#jal-chained-quiz-custom");
        quiz.init();
    }

    /**
     * Check the page for any videos we might have
     * @type {*|jQuery|HTMLElement}
     */
    var $allVideos = $("iframe[src^='https://player.vimeo.com'],iframe[src^='http://player.vimeo.com'],iframe[src^='https://www.youtube.com'],iframe[src^='http://www.youtube.com'],iframe[src^='//www.youtube.com']");

    if($allVideos.length > 0)
    {
        window.intervalTicks = 0;
        window.ytInterval = setInterval(function(){
            for(var vid=0; vid < $allVideos.length; vid++)
            {
                var vidElContent = $allVideos.get(vid);
                var vidElement = $(vidElContent);
                if (!vidElement.hasClass("lazyloading")) {
                    var replace = "<div class=\"jal-responsive-video\">" + vidElContent.outerHTML + "</div>";
                    vidElement.replaceWith( replace );
                    console.log("Video found, replaced for responsiveness.");
                    console.log(vidElContent);
                    intervalTicks++;
                }
            }
            if (intervalTicks >= 5) {
                clearInterval(window.ytInterval);
                console.log("No longer applying responsiveness");
            }
        }, 300);
    }

    /**
     * If we have our infinite scroll module enabled
     */
    if( $("#infinite-loop").length > 0 )
    {
        window.looper = new JalInfiniteLoop();
        var target = $("#infinite-loop");
        looper.setTargetElement( target );
        looper.setOpts({
            'post_type':target.attr("data-post-type"),
            'current_page':target.attr("data-current-page"),
            'category':target.attr("data-category"),
            'endpoint':window.jal_ajax_url
        });
        looper.init();
    }

    /**
     * If we have a Settlements filtering plugin on the page
     */
    if( $("#claim-results-filtering").length > 0 )
    {
        window.settlement_filter = new JalSettlementsFilter();
        settlement_filter.init();
    }

    /**
     * If we have search results
     */
    if( $("#jal-page-search-results").length > 0 )
    {
        var keyword = $("#jal-search-results-term").val();
        /*highlight_words(keyword, "#jal-page-search-results span" );
        highlight_words(keyword, "#jal-page-search-results p" );*/
        $("#jal-any-element-primary").mark(keyword,{
            separateWordSearch:false
        });
    }

    /**
     * If we have the open desktop search button
     */
    if( $("#open-desktop-search").length > 0 )
    {
        $(document).on("click","#open-desktop-search",function(e){
            $("#hidden-desktop-search-bar").toggleClass("open");
            $(this).toggleClass("open");
        });
    }

    /**
     * When wheeling around, if the menu is in responsive mode, we want
     * to see which way they're scrolling
     */
    var lastScrollTop = 0;

    /*var throttledScrollEvent = function()
    {
        var current_window_size = $(window).width();
        var st = $(window).scrollTop();
        var menuIsOpen = $(".jal-responsive-menu").hasClass("jal-responsive-menu-open");
        if( current_window_size < 550 && !menuIsOpen )
        {
            if (st > lastScrollTop){
                console.log("Scrolling DOWN");
                $("#jal-row-element-header-container").addClass("hidden");
            } else {
                console.log("Scrolling UP");
                $("#jal-row-element-header-container").removeClass("hidden");
            }
            lastScrollTop = st;
        }
    };*/

    // $(window).scroll($.throttle(300, throttledScrollEvent ));

    /**
     * Create the Chat Bubble after about 5 seconds of load time
     *
     */
    setTimeout(function(){
        $("<style>@media screen and (max-width: 600px) { #apex-image-chat{display: none !important;} }</style>").appendTo("body");
        $("<style>@keyframes shadow-pulse{0%{box-shadow:0 0 0 0 rgba(0,0,0,.2);-webkit-transform:scale(.9);-moz-transform:scale(.9);-ms-transform:scale(.9);-o-transform:scale(.9);transform:scale(.9)}100%{box-shadow:0 0 0 35px transparent;-webkit-transform:scale(1);-moz-transform:scale(1);-ms-transform:scale(1);-o-transform:scale(1);transform:scale(1)}}#apex-image-chat:hover{background-image:url(https://jonesactlaw.com/wp-content/uploads/2019/09/ChatButton-blue.webp)}#apex-image-chat{font-size:58px;color:#b50f1e;animation:shadow-pulse 1s infinite;z-index:999999;cursor:pointer;bottom:68px;right:33px;display:block;position:fixed;width:80px;height:80px;border-radius:80px;text-align:center;background-image:url(https://jonesactlaw.com/wp-content/uploads/2019/09/ChatButton-red.webp);background-position:center;text-shadow:5px 5px 2px rgba(0,0,0,.1)}</style>").appendTo("body");
        $("<a id=\"apex-image-chat\" onclick=\"window.open('https://www.apex.live/pages/chat.aspx?companyId=27137&requestedAgentId=25&originalReferrer='+document.referrer+'&referrer='+window.location.href,'','width=380,height=570');\"></a>").appendTo("body");
    },2000);

    /**
     * Start the chat
     */
    window.jalChatApplication = new JalChatPopup();
    window.jalChatApplication.init();

});

/**
 * JAL - Word Highlighting
 */
function highlight_words(keywords, element) {
    if(keywords) {
        var textNodes;
        var orig = keywords;
        keywords = keywords.replace(/\W/g, '');
        var str = keywords.split(" ");
        $(str).each(function() {
            var term = this;
            var textNodes = $(element).contents().filter(function() { return this.nodeType === 3 });
            textNodes.each(function() {
                var content = $(this).text();
                var regex = new RegExp(term, "gi");
                content = content.replace(regex, '<span class="highlight">' + orig + '</span>');
                $(this).replaceWith(content);
            });
        });
    }
}

/**
 * JAL - Custom Settlements Filter
 * @constructor
 */
function JalSettlementsFilter()
{
    /**
     * All the elements we'll be filtering
     * @type {*|jQuery|HTMLElement}
     */
    this.filter_elements = $(".crf-filter");

    /**
     * The active filter we currently have
     * @type {string}
     */
    this.active_filter = false;

}

JalSettlementsFilter.prototype.init = function()
{
    this.setHandlers();
};

JalSettlementsFilter.prototype.setHandlers = function()
{
    // New filters--this is just a dropdown now that changes, no need to track state
    $(document).on("change","#claim-results-filtering",function(e){
        var filter_for = $(this).val();
        window.settlement_filter.resetFilterSelections();
        if(filter_for != 0)
        {
            window.settlement_filter.active_filter = filter_for;
            window.settlement_filter.applyFilters(filter_for);
        }
    });

    // Old school filters, keep this in case we revert
    this.filter_elements.on('click',function(){

        // Define if this element is selected, and what we're gonna filter by
        var is_selected = $(this).attr("data-selected");
        var filter_for = $(this).attr("data-for");

        window.settlement_filter.resetFilterSelections();

        // If it's not selected, select it. If it is already selected .. etc.
        if( is_selected == "false" ){
            $(this).attr("data-selected", "true");
            $(this).addClass("active-filter");
            window.settlement_filter.active_filter = filter_for;
            window.settlement_filter.applyFilters(filter_for);
        }else
        {
            $(this).attr("data-selected","false");
        }
    });
};

JalSettlementsFilter.prototype.resetFilterSelections = function()
{
    // Regardless, all elements get the class removed, and filter is removed
    window.settlement_filter.filter_elements.removeClass("active-filter");
    window.settlement_filter.filter_elements.attr("data-selected","false");
    window.settlement_filter.removeFilters();
};

JalSettlementsFilter.prototype.applyFilters = function(filter)
{
    console.log("Would filter settlements by " + filter);
    var filtered_elements = $(".jal-li-settlement-card."+filter);
    if( filtered_elements.length > 0 )
    {
        $(".jal-li-settlement-card").hide();
        filtered_elements.show();
    }else{
        alert('There are no settlements in that category yet. Showing all settlements instead.')
        window.settlement_filter.resetFilterSelections();
    }
};

JalSettlementsFilter.prototype.removeFilters = function()
{
    console.log("Would remove all filters");
    window.settlement_filter.active_filter = false;
    $(".jal-li-settlement-card").show();
};

/** JAL - Custom Chat Popup
 *  @constructor
 *
 *  This class will handle the custom chat pop-up that will appear on the site.
 *  All styles will be inline to see if we can skip making the site too much
 *  slower.
 *
 */
function JalChatPopup() {
    console.info("[JAL][CHAT] Loaded 'Custom Chat Popup'");

    // We want to keep track of localStorage to see if the user has closed the pop-up already
    // If the user interacts with parts of the pop-up at all, we also disable it from appearing again
    // Define any other rules here, but basically, generate the popup on run-time

    /**
     * The html string we'll use to define the DOM element
     * @type {string}
     */
    this.popupHtmlString = "";

    /**
     * The actual DOM element on the page -- use this once the pop-up has been displayed
     * @type {string}
     */
    this.popupHtmlDomElement = null;

    /**
     * Is the pop-up currently visible?
     * @type {boolean}
     */
    this.isPopupVisible = false;

    /**
     * Has the user closed the pop-up already?
     * @type {boolean}
     */
    this.userHasClosedPopup = false;

    /**
     * Has the user clicked on any part of the pop-up already?
     * @type {boolean}
     */
    this.userHasUsedPopup = false;

    /**
     * We can try to store the last date that they saw this
     * @type {number}
     */
    this.userLastTimePopupShown = 0;

    /**
     * The latest timestamp that the user visited the page
     * @type {number}
     */
    this.userLastVisitTime = 0;

    /**
     * The last time we showed the popup
     * @type {number}
     */
    this.userLastPopupShownTime = 0;

    /**
     * The amount of times the popup has been shown
     * @type {number}
     */
    this.userPopupCount = 0;

    /**
     * Tracks if we already showed the exit popup (in the existing session)
     *
     * @type {boolean}
     */
    this.wasShownExitPopup = false;

    /**
     * Tracks if this session has already seen the popup
     *
     * @type {boolean}
     */
    this.wasShownRegularPopup = false;

    /**
     * The ID we'll use to identify the DOM
     * @type {string}
     */
    this.elementId = "jalChatPopup";
}

JalChatPopup.prototype = {
    init: function() {
        /**
         * Check to see if we have these keys in local storage first...
         * - popupHtmlString
         * - userHasClosedPopup
         * - userHasUsedPopup
         * - userLastTimePopupShown
         *
         * If local storage is not available, sorry kid, you're always gonna
         * get the popup.
         *
         */
        if (typeof window.localStorage != "undefined") {

            var keys = [
                'userLastVisitTime',
                'userLastPopupShownTime',
                'userPopupCount'
            ];

            for (var i = 0; i < keys.length; i++) {
                if (window.localStorage.getItem(keys[i])) {
                    console.log("[JAL][Chat] User had existing key ... " + keys[i]);
                    this[keys[i]] = window.localStorage.getItem(keys[i]);
                } else {
                    console.log("[JAL][Chat] User does not have existing key for ... " + keys[i] + " will be set on the next round");
                }
            }

            // Track the last visit as NOW
            window.localStorage.setItem("userLastVisitTime", Math.floor(Date.now() / 1000));
            console.log("[JAL][Chat] Tracking the last visit time as NOW");

        } else {
            // If possible, we can try to use cookies though.
            console.log("[JAL][Chat] Local storage not found, will need to always show pop-up.");
        }

        /**
         * Now that everything's been set... build the popup and activate it.
         *
         */
        var _this = this;
        _this.compilePopup();
        _this.togglePopup();
        _this.setHandlers();
    },
    compilePopup: function (message) {
        if (this.popupHtmlString == "") {
            var windowOpenString = 'https://www.apex.live/pages/chat.aspx?companyId=27137&requestedAgentId=25&originalReferrer='+document.referrer+'&referrer='+window.location.href;
            this.popupHtmlString =
                '<style>.hiddenChatBox {display:none;} .visibleChatBox {display:block;}</style>'+
                '<style>#jalChatClosePopup {cursor:pointer; position:absolute;background-color:white;width:20px;height:20px;border-radius:20px;right: -10px;font-size: 15px;text-align: center;color: gray; font-family: monospace, sans-serif; top: -10px;box-shadow: 0px 0px 10px black;font-weight: bold;background-color: white;}</style>'+
                '<style>'+
                    '.jalTopChatMessage {white-space:nowrap; display:block; font-size:24px; font-weight: bold; text-transform: uppercase; margin-bottom:15px;} #theChatMessage {display:block; font-size:18px}'+
                    '#jalChatInteractPopup {cursor: pointer;background-color: #1978BA; display:block; width:300px; font-weight: bold; font-kerning: 10px; border-radius:5px; text-decoration:none !important; padding:15px 0px; color: white !important; text-transform: uppercase; text-align: center;}'+
                    '#jalChatInteractPopup:hover {background-color:#1978BA; opacity: 0.9}'+
                    '#jalChatInteractPopupClose {background-color: transparent; display:block; width:300px; font-weight: 100; border-radius:5px; padding:15px 0px; color: gray !important; text-align: center;}'+
                    '#timChatPic{position:absolute;bottom: -1px;right:-15px; width:203px;}'+
                    '@media screen and (max-width: 1040px) {#theMainChatContainer {width:75% !important;}}'+
                    '@media screen and (max-width: 767px) {#timChatPic {display:none !important;} #jalChatDivParent{text-align: center;} .jalTopChatMessage{white-space: normal}}'+
                    '@media screen and (max-width: 767px) {#jalChatInteractPopupClose,#jalChatInteractPopup {width:100%; margin:0 auto;}}'+
                '</style>'+
                '<div id="blackClosesThis" style="width:100%; height:100%; background-color:rgba(0,0,0,0.8); position: fixed; top:0px; left:0px; z-index:999999;">'+
                    '<div id="theMainChatContainer" style="background-color:white; width:55%; padding:30px; min-width:250px; max-width:640px; position:absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">'+
                        '<img id="timChatPic" src="/Tim-profilepic-300x600.webp" />'+
                        '<div id="jalChatClosePopup" style="">X</div>'+
                        '<div id="jalChatDivParent" class="container-fluid">' +
                            '<div class="row">' +
                                '<div class="col-md-8 col-sm-6 col-xs-12">'+
                                    '<span class="jalTopChatMessage">Put your worries to rest.</span>'+
                                    '<span id="theChatMessage">Chat with our team of maritime injury law professionals.</span> <br /><a id="jalChatInteractPopup" onclick="window.open(\''+windowOpenString+'\', \'\',\'width=380,height=570\');" href="#">Let\'s Chat</a>'+
                                    '<a id="jalChatInteractPopupClose" href="#">No Thanks</a>'+
                                '</div>'+
                                '<div class="col-md-4 col-sm-6">'+
                                '</div>'+
                            '</div>'+
                        '</div>'+
                    '</div>'+
                '</div>';
        }

        /**
         * Using the popupHtmlString that we have, we continue to add it with display none (activate comes right after
         * anyways..)
         *
         */
        var $div = $("<div id='"+this.elementId+"' style='width:100%; height:100%;'></div>");
        $("body").prepend($div);

        // - Add it to the dom, hide it and let's see if activate will work on it
        this.popupHtmlDomElement = $("#"+this.elementId);
        this.popupHtmlDomElement.html(this.popupHtmlString);
        this.closePopup();
    },
    togglePopup: function() {
        if (this.popupHtmlDomElement) {

            var _this = this;

            /*setTimeout(function() {
             console.log("[JAL][Chat] New code uploaded on 02/23/20");
             _this.openPopup();
             }, 20000);*/

            if (typeof Date !== "undefined") {

                // The current time, in seconds (linux tstamp)
                var currently = Math.floor(Date.now() / 1000);
                console.log("CURRENTLY " + currently);

                // The last time the user opened this page
                var userLastVisitTime = this.userLastVisitTime;
                console.log("LAST VISIT WAS " + this.userLastVisitTime);

                // The last time the popup was shown (not in this session)
                var userLastPopupTime = this.userLastPopupShownTime;
                console.log("LAST POPUP WAS SHOWN AT " + this.userLastPopupShownTime);

                // The difference between NOW and when the user last visited the site
                var visitedDifference = currently - userLastVisitTime;
                var shownDifference = currently - userLastPopupTime;

                console.log("THE SHOWN DIFFERENCE " + shownDifference);

                // # 1 - If popup has not been shown, wait until the current time is past 60 seconds from last visit
                /*if (this.userLastPopupShownTime == 0 && (visitedDifference >= 60)) {
                 console.log("[JAL][Chat] Its been ONE minute");
                 this.openPopup();
                 }*/

                // # 2 - If the popup has been shown, and its been over 1 hour
                if (this.userLastPopupShownTime > 0 && shownDifference >= (1 * 60 * 60)) {
                    // We also reset the last visit time because we want it to trigger again
                    console.log("[JAL][Chat] Its been 1 hour at least");
                    _this.openPopup();
                } else {
                    console.log("[JAL][Chat] It hasn't been an hour yet");
                }

                // # 4 - If the popup has been shown, and its been over 1 hour
                if (this.userLastPopupShownTime > 0 && shownDifference >= (24 * 60 * 60)) {
                    // We also reset the last visit time because we want it to trigger again
                    console.log("[JAL][Chat] Its been 24 hours, resetting for this user");
                    window.localStorage.setItem("userLastVisitTime", 0);
                    window.localStorage.setItem("userLastPopupShownTime",0);
                    setTimeout(function(){
                        _this.openPopup();
                    },20000)
                } else {
                    console.log("[JAL][Chat] It hasn't been 24 hours yet");
                }

                // # 3 - If the popup hasn't been shown yet, and 30 seconds passes
                if (this.userLastPopupShownTime == 0) {
                    console.log("[JAL][Chat] Scheduling to show in 30 seconds");
                    setTimeout(function(){
                        _this.openPopup();
                    },20000)
                } else {
                    console.log("[JAL][Chat] The user has seen this before.. not scheduling to show");
                }

            } else {
                // Sorry. Always open the popup after 60000, regardless
                setTimeout(function(){
                    _this.openPopup();
                },20000)
            }
        } else {
            console.log("[JAL][Chat] Tried to activate chat before it existed.");
        }
    },
    closePopup: function() {
        // Don't show popup dialog
        this.popupHtmlDomElement.removeClass("visibleChatBox");
        this.popupHtmlDomElement.addClass("hiddenChatBox");
        this.isPopupVisible = false;
        console.log("[JAL][Chat] .. Closing Chat Box!");
    },
    openPopup: function() {
        if (!this.wasShownRegularPopup) {
            // Don't show popup dialog
            this.popupHtmlDomElement.addClass("visibleChatBox");
            this.popupHtmlDomElement.removeClass("hiddenChatBox");
            this.isPopupVisible = true;

            console.log("[JAL][Chat] .. Opening Chat Box!");

            // If we have the availability to save time...
            if (typeof Date !== "undefined") {
                this.userLastPopupShownTime = Math.floor(Date.now() / 1000);
                window.localStorage.setItem("userLastPopupShownTime", Math.floor(Date.now() / 1000));
                console.log("[JAL][Chat] Storing the date as " + Math.floor(Date.now() / 1000));
            }

            this.wasShownRegularPopup = true;
        }
    },
    emergencyPopup: function(){

        // The current time, in seconds (linux tstamp)
        var currently = Math.floor(Date.now() / 1000);

        // The last time the user opened this page
        var userLastVisitTime = this.userLastVisitTime;

        // The last time the popup was shown (not in this session)
        var userLastPopupTime = this.userLastPopupShownTime;

        // The difference between NOW and when the user last visited the site
        var shownDifference = currently - userLastPopupTime;

        if (!this.wasShownExitPopup && shownDifference > (10 * 60)) {
            $("#theChatMessage").text("Before you leave, is there anything we can help with?");
            this.openPopup();
            this.wasShownExitPopup = true;
        }
    },
    userClosesPopup: function(){
        // When a user closes the pop up, we do several things.
        // First we mark that the user closed it ... then we store that in local storage
        // This way, next time the user goes to a page, they don't see it.
        this.closePopup();
        this.userHasClosedPopup = true;
        window.localStorage.setItem("userHasClosedPopup", true);
    },
    userInteractsPopup: function() {
        // This is a little different because it may affect how we re-engage the user in the future
        // I.E we may want to show it a bit more often if they like the pop-up
        // Regardless, we still track the time
        this.closePopup();
        this.userHasUsedPopup = true;
        window.localStorage.setItem("userHasUsedPopup", true);
    },
    setHandlers: function() {
        console.log("[JAL][Chat] Setting handlers");
        $(document).on("click", "#blackClosesThis", function(){
            window.jalChatApplication.userClosesPopup();
        });
        $(document).on("click", "#jalChatInteractPopupClose", function(){
            window.jalChatApplication.userClosesPopup();
        });
        $(document).on("click", "#jalChatClosePopup", function(){
           window.jalChatApplication.userClosesPopup();
        });
        $(document).on("click","#jalChatInteractPopup", function(){
           window.jalChatApplication.userInteractsPopup();
        });
        document.onmousemove = function(e){
            var maxHorizontalValue = $(window).width() - 200;
            if (e.pageX >= maxHorizontalValue && e.pageY <= 200) {
                window.jalChatApplication.emergencyPopup();
            }
        };
    }
};


/**
 * JAL - Custom Infinite Loop
 * @constructor
 *
 * This class will handle AJAX requests that will bite us in the ass down the road.
 */
function JalInfiniteLoop()
{
    console.info("[JAL][LOOPER] Loaded 'JAL Infinite Loop'");

    /**
     * The target element we're we are dropping off content
     * @type {null}
     */
    this.target_element = null;

    /**
     * The current page we've loaded
     * @type {number}
     */
    this.current_page = 0;

    /**
     * The post type we want to see
     * @type {string}
     */
    this.post_type = "post";

    /**
     * The type of category we want to see
     * @type {boolean}
     */
    this.category = false;

    /**
     * AJAX Public Endpoint
     * @type {string}
     */
    this.endpoint = "";

    /**
     * Check to see if we're still loading results
     * @type {boolean}
     */
    this.is_loading_results = false;

    /**
     * Check to see if the results have been loaded yet
     * @type {boolean}
     */
    this.is_loaded_results = false;

    /**
     * Checks to see if we should stop loading results
     *
     * @type {boolean}
     */
    this.stop = false;

}

JalInfiniteLoop.prototype =
{
    init:function()
    {
        // Sets up the HTML we need
        this.setupHtml();

        // Sets up some handlers
        this.setHandlers();

    },
    trigger:function()
    {
        if( !this.is_loading_results && !this.stop )
        {
            console.log("[JAL][LOOPER] Will attempt to load more content...");
            $.ajax({
                url:this.endpoint,
                type:'POST',
                data:{
                    action:'jallooper',
                    page: parseInt(looper.current_page) + 1,
                    post_type: looper.post_type,
                    category: looper.category
                },
                beforeSend:function()
                {
                    // Let the window know we're starting up
                    window.looper.is_loading_results = true;
                    window.looper.is_loaded_results = false;

                    looper.target_element.addClass('loading');
                }
            }).done(function(r){

                setTimeout(function(){

                    $("#click-load-more").remove();

                    // Add the results to the screen
                    looper.target_element.append(r);
                    looper.current_page = parseInt(looper.current_page) + 1;

                    console.info("Current page is now ... " + looper.current_page );

                    // Let the window know we're done
                    window.looper.is_loading_results = false;
                    window.looper.is_loaded_results = true;

                    looper.target_element.removeClass('loading');

                    if( r == "" || r == '<h2 style=\'text-align: center\'>No more posts to show!</h2>' )
                    {
                        looper.stop = true;
                    }else
                    {
                        looper.target_element.append("<a class='btn btn-default' id='click-load-more' href='javascript:void(0);'>Load More Posts</a>");
                    }

                },0);
            });
        }
    },
    setupHtml:function()
    {
        this.target_element.html("<div id='jal-looper-loading-element'>Loading...</div>");
        this.target_element.append("<a class='btn btn-default' id='click-load-more' href='javascript:void(0);'>Load More Posts</a>");
    },
    setHandlers:function()
    {
        /*$(window).on('scroll',function(){
            looper.detect();
        });*/
        $(document).on('click',"#click-load-more",function(){
           window.looper.trigger();
        });
    },
    detect:function()
    {
        /*var elementTop = this.target_element.offset().top;
        var elementBottom = elementTop + this.target_element.outerHeight();
        var viewportTop = $(window).scrollTop();
        var viewportBottom = viewportTop + $(window).height();*/
        var detectionResult = 0;

        if($(window).scrollTop() + $(window).height() == $(document).height())
        {
            detectionResult = 1;
        }

        if(detectionResult == 1)
        {
            this.trigger();
        }
    },
    setTargetElement:function(el)
    {
        this.target_element = el;
    },
    setOpts:function(opts)
    {
        var defaults = {
            post_type:"post",
            endpoint:"admin-ajax.php",
            category:"",
            current_page:1
        };

        if(typeof opts != "undefined"){
            for(prop in defaults)
            {
                if(opts.hasOwnProperty(prop)){
                    defaults[prop] = opts[prop];
                }
            }
        }

        for(prop in defaults)
        {
            if(defaults.hasOwnProperty(prop)){
                this[prop] = defaults[prop];
            }
        }
    }
};

/**
 * JAL - Custom Chained Quiz
 * This class will help us create a similar experience to the previous quiz, with
 * a little more interaction.
 *
 **/
function JalChainedQuiz()
{
    /*
    Let our developer know we've loaded succesfully
     */
    console.info("[JAL] Loaded 'Custom Chained Quiz'");

    /**
     * This is the primary quiz element (parent)
     * @type {null}
     */
    this.parent_element = null;

    /**
     * This is the total amount of steps this quiz will have (used to determine what controls to use)
     * @type {number}
     */
    this.total_steps = 0;

    /**
     * Define which step we're in currently (if none set, we assume step 1)
     * @type {number}
     */
    this.current_step = 1;

    /**
     * Lets us quickly know if we're at the submission Gate
     * @type {boolean}
     */
    this.is_in_gate = false;

    /**
     * Lets us quickly know if we're at the last step (results)
     * @type {boolean}
     */
    this.is_in_results = false;

    /**
     * A list of the questions and answers selected
     * @type {Array}
     */
    this.answers = [];

    /**
     * The final tally of the total score
     * @type {number}
     */
    this.total_score = 0;

    /**
     * The name of the user who submitted the form
     * @type {string}
     */
    this.user_name = "Not given";

    /**
     * The email of the user who submitted the form
     * @type {string}
     */
    this.user_email = "Not given";
}

JalChainedQuiz.prototype =
{
    /**
     * Sets our local parent (used for data storage mostly, but also children selectors)
     * @param selector
     */
    setTarget:function(selector)
    {
        /**
         * Find the element based on the selector provided (this gives us a scope)
         * @type {*|jQuery|HTMLElement}
         */
        var element = $(selector);
        if(typeof element != "undefined"){
            this.parent_element = element;
        }else{
            console.error("[JAL][QUIZ] The selector provided does not exist on the page!");
        }
    },
    /**
     * Initializes the Quiz with the information we know from the parent element
     */
    init:function()
    {
        if(this.parent_element.length > 0) {

            /* If the window is smaller than 550, auto scroll to top of quiz, always */
            /*if( $(window).width() <= 550 )
            {
                $(window).scrollTo( window.quiz.parent_element.offset().top - 110 );
            }*/

            /* Define the step count (not including the gate and results pages) */
            this.total_steps = parseInt(this.parent_element.attr("data-total-steps"));

            /* Update all the local answers */
            this.setAnswerData();

            /* Find out which step we're in currently, to determine a starting point for controls */
            this.updateCurrentStep();

            /* Finally, update controls to reflect what we need for now */
            this.updateControls();

            /* Well, also set handlers up */
            this.setHandlers();

        }else{
            console.error("[JAL][QUIZ] No parent element to get data from!");
        }
    },
    /**
     * We need to extract the answer information from the text, because the quiz formatting blows.
     */
    setAnswerData:function()
    {
        for(var i=1; i<=this.total_steps; i++)
        {
            var answer_element = $("div[data-jal-quiz-step='"+i+"']");
            var choice_element = $("input[name='jal-chained-quiz-"+i+"']");
            if(answer_element.length > 0 && choice_element.length > 0)
            {
                var option_head = $( answer_element.find(".option-head").get(0) );
                if(option_head.length > 0)
                {
                    choice_element.attr("data-answer", option_head.text());
                }else{
                    choice_element.attr("data-answer", "Answer unknown");
                }
            }
        }
    },
    /**
     * This will automatically (locally) update where we are currently in the quiz
     */
    updateCurrentStep:function()
    {
        /* Find any currently active steps */
        var currently_active_steps = this.parent_element.find(".jal-chained-quiz-content.active");

        /* Set our current step to the first one we find */
        if( currently_active_steps.length > 0){
            var currently_active_step = $(currently_active_steps.get(0));
            this.current_step = parseInt(currently_active_step.attr("data-jal-quiz-step"));
        }

        /* Let our system know if we're at the gate */
        if( $("#jal-chained-quiz-content-gate").hasClass("active") )
        {
            console.log("[JAL][QUIZ] We made it to the CONTENT GATE.")
            this.is_in_gate = true;
        }else{
            this.is_in_gate = false;
        }

        /* Let our system know if we're at the results */
        if( $("#jal-chained-quiz-content-result").hasClass("active") )
        {
            console.log("[JAL][QUIZ] We made it to the RESULT GATE.")
            this.is_in_results = true;
        }else{
            this.is_in_results = false;
        }
    },
    /**
     * Updates the controls to reflect our current step properly (namely, just says which step is next and also
     * hides the 'next' button if we're out of our range)
     */
    updateControls:function()
    {
        /* First, hide all controls (if not already hidden) */
        $(".jal-chained-quiz-control").hide();

        /* If our CURRENT step is LESS than or EQUAL to the total number of steps, show our next button */
        if( this.current_step <= this.total_steps )
        {
            var our_next_control = $( this.parent_element.find(".jal-chained-quiz-control-next").get(0) );
            our_next_control.show();
        }

        /* If we're in the gate, let's show that submit button */
        if( this.is_in_gate )
        {
            var our_gate_control = $( this.parent_element.find(".jal-chained-quiz-control-gate-next").get(0) );
            our_gate_control.show();
        }

        /* If we're in the gate, let's show that submit button */
        if( this.is_in_results )
        {
            var our_result_control = $( this.parent_element.find(".jal-chained-quiz-control-redo").get(0) );
            our_result_control.show();
        }
    },
    /**
     * This will load the contact values from the form provided -- this will also return a validation error
     * if either of them are un filled
     */
    updateContactValues:function()
    {
        /**
         * This is the validation result we'll send back for any one interested
         * @type {boolean}
         */
        var will_return = false;
        var name = $("#jal-chained-quiz-name-input").val();
        var email = $("#jal-chained-quiz-email-input").val();

        /**
         * If we have values, we'll return yes and set them locally
         */
        if( name && email )
        {
            will_return = true;
            this.user_name = name;
            this.user_email = email;
        }

        return will_return;
    },
    /**
     * Moves the quiz to the desired step
     * @param step
     */
    gotoStep:function(step)
    {
        console.log("[JAL][QUIZ] Moving to step ... " + step);
        if( typeof step != "undefined" )
        {
            this.parent_element.fadeTo("fast",0.5);
            var step_element = this.parent_element.find(".jal-chained-quiz-content[data-jal-quiz-step='"+step+"']");
            if(step_element.length > 0)
            {
                var _self = this;
                /* Simulated loading time */
                setTimeout(function(){

                    /* First, remove active from all steps */
                    quiz.parent_element.find(".jal-chained-quiz-content").removeClass("active");
                    step_element.addClass("active");
                    quiz.updateCurrentStep();
                    quiz.updateControls();
                    quiz.parent_element.fadeTo("fast",1);

                },500);
            }else{
                console.error("[JAL][QUIZ] Could not move to this step -- the element doesn't exist!");
            }
        }
    },
    /**
     * Find out what the Result step is, and GOTO there
     */
    gotoResults:function()
    {
        var result_element = $("#jal-chained-quiz-content-result");
        if(result_element.length > 0)
        {
            var step = parseInt(result_element.attr("data-jal-quiz-step"));
            this.gotoStep(step);
        }else{
            console.error("[JAL][QUIZ] No results page defined!");
        }
    },
    /**
     * Shortcut method to going to the next step
     */
    nextStep:function()
    {
        var current_step = this.current_step;
        var next_step = current_step + 1;
        var answer = this.findSelectedAnswerForStep(current_step);
        if(answer){
            this.gotoStep( next_step );
        }else{
            this.updateModalContent({
                title:"We Need More Info", content:"Please select an answer for this question before moving on!"
            });
            this.openModal();
        }
    },
    /**
     * This function will loop through each step, find out what the user has selected so far, and add it as a tally
     */
    updateScore:function()
    {
        var score = 0;
        for(var i=1; i<=this.total_steps;i++)
        {
            var radio_element = $("input[name='jal-chained-quiz-"+i+"']:checked");
            if( radio_element.length > 0 )
            {
                score = score + parseInt(radio_element.val());
            }else{
                console.error("[JAL][QUIZ] Couldn't find a matching radio element for " + i);
            }
        }
        this.total_score = score;
        console.log("[JAL][QUIZ] Total score is now ... " + score);
    },
    /**
     * This function will loop through each answer, and set it locally for when we're ready to submit
     */
    updateAnswers:function()
    {
        var answers = [];
        for(var i=1; i<=this.total_steps;i++)
        {
            var radio_element = $("input[name='jal-chained-quiz-"+i+"']:checked");
            var question_element = $("div[data-jal-quiz-step='"+i+"']");
            if( radio_element.length > 0 )
            {
                var insert = {
                    question:"Question " + i,
                    question_id:question_element.attr("data-question-id"),
                    answer: radio_element.attr("data-answer"),
                    answer_id: radio_element.attr("data-answer-id"),
                    points: parseInt(radio_element.val())
                };
                if(question_element.length > 0)
                {
                    var full_question_text = $(question_element.find(".question_full").get(0)).text();
                    insert.question = full_question_text;
                }
                answers.push(insert);
            }else{
                console.error("[JAL][QUIZ] Couldn't find a matching radio element for " + i);
            }
        }
        this.answers = answers;
    },
    /**
     * This function (method) will update the quiz's modal content with the provided
     * @param opts
     */
    updateModalContent:function(opts)
    {
        var defaults = {
            title:"No Title Given",
            content:"No Content Given"
        };
        if(typeof opts != "undefined" && opts.hasOwnProperty("title")){
            defaults.title = opts.title;
        }
        if(typeof opts != "undefined" && opts.hasOwnProperty("title")){
            defaults.content = opts.content;
        }

        $("#jal-chained-quiz-modal-title").text(defaults.title);
        $("#jal-chained-quiz-modal-body").text(defaults.content);
    },
    /**
     * This function will open the quiz's modal
     */
    openModal:function()
    {
        $("#jal-chained-quiz-modal").show();
    },
    /**
     * This function will close the quiz's modal
     */
    closeModal:function()
    {
        $("#jal-chained-quiz-modal").hide();
    },
    /**
     * This function will help us find the answer for a given step -- if none is provided, we can
     * return false -- otherwise we can return the answer string itself.
     * @param step
     */
    findSelectedAnswerForStep:function(step)
    {
        if(step > 0)
        {
            var radio_element = $("input[name='jal-chained-quiz-"+step+"']:checked");
            if(radio_element.length > 0){
                return radio_element.attr("data-answer");
            }else{
                return false;
            }
        }else{
            return false;
        }
    },
    /**
     * This function will actually submit the answers to our ajax, get the results, and process any infusionsoft
     * programming necessary
     */
    submit:function()
    {
        /**
         * Update all the answers the user has given so far at the time of submission
         */
        this.updateAnswers();

        /**
         * Update the final score as well, again based on the time of submission
         */
        this.updateScore();

        /**
         * Get the validation result from the form first
         */
        var validates = this.updateContactValues();

        if( validates )
        {
            /**
             * Make a call outbound to a) show the final results page and b) submit e-mail (and infusionsoft)
             */
            $.ajax({
                url:window.jal_ajax_url,
                type:"POST",
                data:{
                    action:'claimcalculator',
                    answers:quiz.answers,
                    score:quiz.total_score,
                    name:quiz.user_name,
                    email:quiz.user_email
                }
            }).done(function(response){
                if(typeof response.result != "undefined")
                {
                    $("#jal-chained-quiz-content-result").html( response.results_html );
                    ga('send', 'event', 'Calculator', 'Click', 'Submitted Calculator Form');
					quiz.gotoResults();
                }
            });
        }else{
            this.updateModalContent({
                title:"We Need More Info",
                content:"Please submit a name and email before you can get your results!"
            });
            this.openModal();
        }
    },
    /**
     * Sets handling for any interactivity on the buttons in the controls
     */
    setHandlers:function()
    {
        /* The 'next' button */
        $(document).off(".jal-chained-quiz-control-next a").on("click",".jal-chained-quiz-control-next a",function(){
            $(window).scrollTo(window.quiz.parent_element.offset().top - 110);
            quiz.nextStep();
        });

        /* Pretty much just reloads the page */
        $(document).off(".jal-chained-quiz-control-redo a").on("click",".jal-chained-quiz-control-redo a",function(){
            window.location.reload();
        });

        /* The 'get results' button */
        $(document).off(".jal-chained-quiz-control-gate-next a").on("click",".jal-chained-quiz-control-gate-next a",function(){
            $(window).scrollTo(window.quiz.parent_element.offset().top - 110);
            quiz.submit();
        });

        /* Expanding answers modal */
        $(document).off(".jal-chained-quiz-expand-modal").on("click",".jal-chained-quiz-expand-modal",function(){

            var parent_label = $(this).parent();

            var parent_dialog = parent_label.find(".dialog_popup");

            var title = parent_dialog.find("span.option-head").text();

            var content = parent_dialog.find("p").text();

            quiz.updateModalContent({
                "title":title, "content":content
            });
            quiz.openModal();
        });

        /* Close the answers modal */
        $(document).off("#jal-chained-quiz-modal").on("click","#jal-chained-quiz-modal",function(){
            quiz.closeModal();
        });
        $(document).off("#jal-chained-quiz-modal-close").on("click","#jal-chained-quiz-modal-close",function(){
            quiz.closeModal();
        });
    }
};