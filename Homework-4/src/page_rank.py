import networkx as nx


graph = nx.read_edgelist("C:\\Users\\black\\Desktop\\CSCI-572\\Homework-4\\edges.txt")
page_rank = nx.pagerank(graph)
with open("external_pageRankFile.txt", "w") as f:
    for key, value in page_rank.items():
        f.write(f"/home/annguyen/shared/data/nytimes/{key}={value}\n")
