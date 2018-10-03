// Add an icon suffix on external links

$(document).ready(function() {
  'use strict';

  const domain_re = /https?:\/\/((?:[\w\d-]+\.)+[\w\d]{2,})/i;
  const url = domain_re.exec(location.href)[1];

  $('a[href^="http"]').each(function() {
    const link = $(this);
    const href = link.attr('href');

    const domain = domain_re.exec(href)[1];

    if (!(link.parent('h1, h2, h3, h4, h5, h6').length || link.has('img').length)) {
      if (!(href.split('.').pop() === 'pdf') && url !== domain) {
        link.addClass('external-link');
      }
    }
  });
});
