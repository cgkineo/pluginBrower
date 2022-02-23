//cross domain support for all other browers
$.ajaxPrefilter(function( options ) {
    options.crossDomain = true;
});

var relativePath = '/wp-content/themes/foundry-child/plugin-page/';

var Core = _.extend({}, Backbone.Events);

var BaseView = Backbone.View.extend({

	initialize: function() {
		this.listenToOnce(Core, "remove", this.onRemove);
		this.render()
	},

	render: function() {
		var template = $("#templates .template[name='" + this.constructor.template + "']").html().slice(4,-3);
		var compile = _.template(template);
		var output = compile(this.model.toJSON());
		this.$el = $(output);
		this.undelegateEvents();
		this.delegateEvents();

		updateResultStat();
	},

	onRemove: function() {
		this.remove();
	}

});

var RepoCollection = Backbone.Collection.extend({

	initialize: function() {
		this.url = relativePath + "php/packages.php";
		this.fetch({
			success: _.bind(this.onSuccess, this),
			error: _.bind(this.onError, this),
		});
	},

	onSuccess: function() {
		this.trigger("dataready");
		$('.page .loading').hide();
	},

	onError: function() {
		this.trigger("dataerror");
	}

});

var ResultsView = BaseView.extend({

	initialize: function() {
		this.renderChildren();
	},

	renderChildren: function() {
		_.each(this.collection.sortBy(function(item) {
			return 1/item.get("hits");
		}), _.bind(function(model) {
			var result = new ResultView({model:model});
			this.$el.append(result.$el);
			filtered.push(result.$el.data("name"));
		}, this));
	},

	onRemove: function (){}

},{
	template: "result"
});


var LightboxView = Backbone.View.extend({

	events: {
		"click #close": "onCloseClick",
		"click #left": "onNavigationClick",
		"click #right": "onNavigationClick",
	},

	initialize: function() {

	},

	onNavigationClick: function(event) {
		event.preventDefault();
		var $target = $(event.currentTarget);
		if ($target.is(".disabled")) return;
		Core.trigger("preview:remove");
		var model = repoCollection.findWhere({bowername: $target.attr("data-name")});
		var preview = new PreviewView({model:model});
		$("#lightbox-container").append(preview.$el);
	},

	onCloseClick: function(event) {
		event.preventDefault();

		$("#shadow, #plugin-lightbox").addClass("hidden");

		var scrollTop = parseInt($('html').css('top'));
		$('html').removeClass('noscroll');
		$('html,body').scrollTop(-scrollTop);

		Core.trigger("remove");
	}

});

var ResultView = BaseView.extend({

	events: {
		"click": "onClick"
	},

	onClick: function(event) {
		var preview = new PreviewView({model:this.model});

		$("#lightbox-container").append(preview.$el.addClass("clearfix"));

		event.preventDefault();

		$("#shadow, #plugin-lightbox").removeClass("hidden");

		if ($(document).height() > $(window).height()) {
		    var scrollTop = ($('html').scrollTop()) ? $('html').scrollTop() : $('body').scrollTop(); // Works for Chrome, Firefox, IE...
		    $('html').addClass('noscroll').css('top',-scrollTop);
		}
	},

	onRemove: function (){}

},{
	template: "result"
});

var PreviewView = BaseView.extend({

	id: "preview",
	tagName: "div",

	events: {
		"click a": "onLinkClick"
	},

	render: function() {
		this.listenToOnce(Core, "preview:remove", this.onRemove);

		var template = $("#templates .template[name='" + this.constructor.template + "']").html().slice(4,-3);
		var compile = _.template(template);
		var output = compile(this.model.toJSON());
		this.$el.empty().append($(output));
		this.undelegateEvents();
		this.delegateEvents();

		$.get(relativePath + "php/readme.php?url="+this.model.get("giturl"), _.bind(function(response) {
			this.$el.find("#readme").empty().append($(response));
		}, this));

		this.setUpNavigation(this.model.get("bowername"));
	},

	setUpNavigation: function(name) {
		var filteredCount = filtered.length;

		var index = filtered.indexOf(name);

		var prevID = index - 1;
		var nextID = index + 1;
		var hasPrev = prevID > -1;
		var hasNext = nextID < filteredCount;

		prevID = filtered[prevID];
		nextID = filtered[nextID];

		$("#left, #right").removeClass("disabled");

		if (hasPrev) {
			$("#left").attr("data-name", prevID);
		} else {
			$("#left").addClass("disabled");
		}

		if (hasNext) {
			$("#right").attr("data-name", nextID);
		} else {
			$("#right").addClass("disabled");
		}
	},

	onLinkClick: function(event) {
		var $target = $(event.currentTarget);
		if (!$target.attr("href")) return;

		switch ($target.attr("href")) {
		case "#top":
			$("#lightbox-container").scrollTop(0);
			return;
		}

		event.preventDefault();
		window.open($target.attr("href"));
	}

},{
	template: "preview"
});

var filtered = [];
var SearchView = BaseView.extend({

	events: {
		"keyup": "onSearchKeyup",
		"focus": "onSearchFocus",
		"blur": "onSearchBlur",
	},

	initialize: function() {},

	onSearchKeyup: function(event) {
		var $repo = $(".repo");
		var input = this.$el.val() ? this.$el.val().split(" ") : "";

		filtered = [];
		$repo.removeClass("hidden");

		if (event.which === 27) {
			return $(this).val("").blur(); // esc key
		}
		if (!input) {
			_.each($repo, function(item){
				filtered.push($(item).data("name"));
			})
			$(".header-search .icon").removeClass("icon-cross").addClass("icon-search");
			updateResultStat();
			return;
		}

		var self = this;
		$(".header-search .icon").removeClass("icon-search").addClass("icon-cross").click(function(e) {
			e.preventDefault();
			$("#search").val("");
			self.onSearchKeyup({});
		});

		$repo.each(function() {
			var $this = $(this);
			var model = repoCollection.findWhere({bowername:$this.data("name")});

			for (var i = 0, j = input.length; i < j; i++) {
				var term = input[i];
				var searchIn = [ model.get("name"), model.get("description"), model.get("type"), model.get("user") ];
				var shouldHide = term && !isMatch(term, searchIn);

				if (shouldHide) {
					$this.addClass("hidden");
				}
			}

			if (!$this.hasClass("hidden")) filtered.push($this.data("name"));
		});

		updateResultStat();

		function isMatch(term, fields) {
			term = term.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');

			var regex = new RegExp( term, "i");

			for (var i = 0, j = fields.length; i < j; i++) {
				if (!fields[i]) continue;
				if (fields[i].match(regex)) return true;
			}
		}
	},

	// onSearchFocus: function() {
	// 	$('.header-search').addClass("expanded");
	// },

	// onSearchBlur: function() {
	// 	if (!this.$el.val()) $('.header-search').removeClass("expanded");
	// }

});

var updateResultStat = function() {
	var count = filtered.length > 0 ? filtered.length : (this.collection && this.collection.length) || 0;
	var str = count + ' plugin' + ((count > 1 || count == 0) ? 's' : '') + ' found.';
	$('#resultsStats').html(str);
}

var repoCollection;
$(function() {
	$('.page .container').show(1);

	new SearchView({el: $("#search")});
	new LightboxView({el: $("#plugin-lightbox")});

	repoCollection = new RepoCollection();

	repoCollection.on("dataready", function() {
		new ResultsView({collection:repoCollection, el: $("#results")});
	});
});
