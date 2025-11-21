#include <iostream>
#include <vector>
using namespace std;

int main() {
  cout << "Hello World!";
  return 0;
}

// Source - https://stackoverflow.com/a
// Posted by Loki Astari, modified by community. See post 'Timeline' for change history
// Retrieved 2025-11-21, License - CC BY-SA 4.0

std::vector<std::string> getNextLineAndSplitIntoTokens(std::istream& str)
{
    std::vector<std::string>   result;
    std::string                line;
    std::getline(str,line);

    std::stringstream          lineStream(line);
    std::string                cell;

    while(std::getline(lineStream,cell, ','))
    {
        result.push_back(cell);
    }
    // This checks for a trailing comma with no data after it.
    if (!lineStream && cell.empty())
    {
        // If there was a trailing comma then add an empty element.
        result.push_back("");
    }
    return result;
}
