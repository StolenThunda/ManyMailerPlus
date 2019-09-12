class Stepper {
    constructor(sections) {
        if (sections) {
            this.sections = sections;

            // Previous button is easy, just go back
            $('.form-navigation .previous').click(function() {
                this.navigateTo(this.curIndex() - 1);
            });

            // Next button goes forward iff current block validates
            $('.form-navigation .next').click(function() {
                $('.demo-form')
                    .parsley()
                    .whenValidate({
                        group: 'block-' + this.curIndex()
                    })
                    .done(function() {
                        this.navigateTo(this.curIndex() + 1);
                    });
            });
            this.navigateTo = function(index) {
                // Mark the current section with the class 'current'
                this.sections.removeClass('current').eq(index).addClass('current');
                // Show only the navigation buttons that make sense for the current section:
                $('.form-navigation .previous').toggle(index > 0);
                var atTheEnd = index >= this.sections.length - 1;
                $('.form-navigation .next').toggle(!atTheEnd);

                $('.form-navigation input[type="submit"]').toggle(atTheEnd);
            };

            this.curIndex = function() {
                // Return the current index by looking at which section has the class 'current'
                return this.sections.index(this.sections.filter('.current'));
            };

            this.getCurrentSlug = function() {
                return this.sections.filter('.current').data('slug');
            };

            // Prepare sections by setting the `data-parsley-group` attribute to 'block-0', 'block-1', etc.
            this.sections.each(function(index, section) {
                $(section).find(':input').attr('data-parsley-group', 'block-' + index);
            });
            this.navigateTo(0); // Start at the beginning
        }
    }
}