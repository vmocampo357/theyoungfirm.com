$(document).ready(function(){
    console.log('Maritime Law Firm JS Active ...');

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
    if( $("#jal-any-sticky-bar").length > 0 )
    {
        $("#jal-any-sticky-bar").stick_in_parent({
            parent: "#jal-row-element-primary-body",
            offset_top:120,
            bottoming:true
        });
    }

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
    var $allVideos = $("iframe[src^='https://www.youtube.com'],iframe[src^='http://www.youtube.com'],iframe[src^='//www.youtube.com']");

    if($allVideos.length > 0)
    {
        for(var vid=0; vid < $allVideos.length; vid++)
        {
            var vidElContent = $allVideos.get(vid);
            var vidElement = $(vidElContent);
            var replace = "<div class=\"jal-responsive-video\">" + vidElContent.outerHTML + "</div>";
            vidElement.replaceWith( replace );
            console.log("Video found, replaced for responsiveness.");
            console.log(vidElContent);
        }
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