import edu.uci.ics.crawler4j.crawler.CrawlConfig;
import edu.uci.ics.crawler4j.crawler.CrawlController;
import edu.uci.ics.crawler4j.fetcher.PageFetcher;
import edu.uci.ics.crawler4j.robotstxt.RobotstxtConfig;
import edu.uci.ics.crawler4j.robotstxt.RobotstxtServer;

import java.io.IOException;
import java.io.PrintWriter;
import java.nio.charset.StandardCharsets;


public class Controller {
    public static void main(String[] args) throws Exception {
        String crawlStorageFolder = "/data/crawl";
        int numberOfCrawlers = 16;
        CrawlConfig config = new CrawlConfig();

        /*
        Setup configuration
         */
        config.setCrawlStorageFolder(crawlStorageFolder);
        config.setIncludeBinaryContentInCrawling(true);
        config.setMaxDepthOfCrawling(16);
        config.setMaxPagesToFetch(20000);
        config.setPolitenessDelay(50);
        config.setUserAgentString("Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36");

        /*
         * Instantiate the controller for this crawl.
         */
        PageFetcher pageFetcher = new PageFetcher(config);
        RobotstxtConfig robotstxtConfig = new RobotstxtConfig();
        RobotstxtServer robotstxtServer = new RobotstxtServer(robotstxtConfig, pageFetcher);
        CrawlController controller = new CrawlController(config, pageFetcher, robotstxtServer);
        /*
         * For each crawl, you need to add some seed urls. These are the first
         * URLs that are fetched and then the crawler starts following links
         * which are found in these pages
         */
        controller.addSeed("https://www.wsj.com");
        /*
         * Start the crawl. This is a blocking operation, meaning that your code
         * will reach the line after this only when crawling is finished.
         */
        controller.start(MyCrawler.class, numberOfCrawlers);

        StringBuilder out1 = new StringBuilder("URL,Status\n");
        StringBuilder out2 = new StringBuilder("URL,Size,Outgoing Links,Content Type\n");
        StringBuilder out3 = new StringBuilder("URL,Status\n");

        for (Object t : controller.getCrawlersLocalData()) {
            String[] tasks = (String[]) t;
            out1.append(tasks[0]);
            out2.append(tasks[1]);
            out3.append(tasks[2]);
        }

        writeCSV(out1, "fetch_wsj.csv");
        writeCSV(out2, "visit_wsj.csv");
        writeCSV(out3, "urls_wsj.csv");
    }

    private static void writeCSV(StringBuilder output, String s) throws IOException {
        PrintWriter writer = new PrintWriter(s, StandardCharsets.UTF_8);
        writer.println(output.toString().trim());
        writer.flush();
        writer.close();
    }
}
