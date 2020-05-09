import edu.uci.ics.crawler4j.crawler.Page;
import edu.uci.ics.crawler4j.crawler.WebCrawler;
import edu.uci.ics.crawler4j.parser.HtmlParseData;
import edu.uci.ics.crawler4j.url.WebURL;

import java.util.HashSet;
import java.util.Set;
import java.util.regex.Pattern;

public class MyCrawler extends WebCrawler {
    private final static Pattern FILTERS = Pattern.compile(
            ".*(\\.(" + "css|js|json|webmanifest|ttf|svg|wav|avi|mov|mpeg|mpg|ram|m4v|wma|wmv|mid|txt|mp2|mp3|mp4|zip|rar|gz|exe|ico))$");

    private String task1 = "";
    private String task2 = "";
    private String task3 = "";
    private HashSet<String> seen = new HashSet<>();

    @Override
    public Object getMyLocalData() {
        return new String[]{task1, task2, task3};
    }

    /*
    TASK 1: Get the URLs the crawler attempts to fetch
     */
    @Override
    protected void handlePageStatusCode(WebURL webUrl, int statusCode, String statusDescription) {
        String url = webUrl.getURL().toLowerCase().replaceAll(",", "_");
        task1 += url + "," + statusCode + "\n";
        seen.add(url);
    }

    /*
     * This method receives two parameters. The first parameter is the page
     * in which we have discovered this new url and the second parameter is
     * the new url. You should implement this function to specify whether
     * the given url should be crawled or not (based on your crawling logic).
     * In this example, we are instructing the crawler to ignore urls that
     * have css, js, git, ... extensions and to only accept urls that start
     * with "http://www.wsj.com/". In this case, we didn't need the
     * referringPage parameter to make the decision.
     *
     * TASK 3: Check if the URL (including repeats) discovered is inside or outside the website
     */
    @Override
    public boolean shouldVisit(Page referringPage, WebURL url) {
        String href = url.getURL().toLowerCase().replaceAll(",", "_");
        boolean isValid = href.startsWith("http://www.wsj.com/") || href.startsWith("https://www.wsj.com/");
        if (isValid)
            task3 += href + ",OK\n";
        else task3 += href + ",N_OK\n";
        boolean hasNotSeen = !seen.contains(href);
        return !FILTERS.matcher(href).matches() && isValid && hasNotSeen;
    }

    /*
     * This function is called when a page is fetched and ready
     * to be processed by your program.
     *
     * TASK 2: Get the files the crawler successfully downloads
     */
    @Override
    public void visit(Page page) {
        String url = page.getWebURL().getURL().toLowerCase().replaceAll(",", "_");
        int numberOfOutLinks = 0;
        int size = page.getContentData().length;
        String contentType = page.getContentType().split(";")[0];

        boolean isCorrectType = contentType.contains("html") | contentType.contains("image") |
                                contentType.contains("doc") | contentType.contains("pdf");
        if (!isCorrectType)
            return;

        if (page.getParseData() instanceof HtmlParseData) {
            HtmlParseData htmlParseData = (HtmlParseData) page.getParseData();
            Set<WebURL> links = htmlParseData.getOutgoingUrls();
            numberOfOutLinks += links.size();
        }

        task2 += url + "," + size + "," + numberOfOutLinks + "," + contentType + "\n";
    }
}
